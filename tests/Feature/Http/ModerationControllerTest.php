<?php

namespace Tests\Feature\Http;

use App\Enums\GroupModeratorRole;
use App\Enums\ModerationCaseStatus;
use App\Enums\ReportTargetType;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ModerationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    private function moderatorOf(Group $group, GroupModeratorRole $role = GroupModeratorRole::Moderator): User
    {
        $user = User::factory()->create();
        app(GroupRepositoryInterface::class)->addModerator($group->id, $user->id, $role);

        return $user;
    }

    public function test_a_guest_is_sent_to_log_in(): void
    {
        $group = Group::factory()->create();

        $this->get(route('groups.moderation', $group->slug))->assertRedirect(route('login'));
    }

    public function test_a_member_and_a_moderator_of_another_group_are_refused(): void
    {
        $group = Group::factory()->create();
        $member = User::factory()->create();
        app(GroupRepositoryInterface::class)->subscribe($group->id, $member->id);
        $elsewhere = $this->moderatorOf(Group::factory()->create());

        $this->actingAs($member)->get(route('groups.moderation', $group->slug))->assertForbidden();
        $this->actingAs($elsewhere)->get(route('groups.moderation', $group->slug))->assertForbidden();
    }

    public function test_an_unknown_group_is_not_found(): void
    {
        $this->actingAs(User::factory()->create())->get(route('groups.moderation', 'no-such-group'))->assertNotFound();
    }

    public function test_a_moderator_sees_the_queue_with_the_hidden_text_and_no_author(): void
    {
        $group = Group::factory()->create();
        $moderator = $this->moderatorOf($group);
        $post = Post::factory()->hidden()->create(['group_id' => $group->id, 'title' => 'Reported title']);
        $case = app(ModerationCaseRepositoryInterface::class)->open($group->id, ReportTargetType::Post, $post->id);

        $this->actingAs($moderator)->get(route('groups.moderation', $group->slug))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Groups/Moderation')
                ->where('role', 'moderator')
                ->where('can_edit', false)
                ->has('cases.data', 1)
                ->where('cases.data.0.id', $case->id)
                ->where('cases.data.0.target.title', 'Reported title')
                ->missing('cases.data.0.target.author')
                ->missing('cases.data.0.target.user_id'));
    }

    public function test_the_owner_may_edit_and_sees_an_empty_queue(): void
    {
        $group = Group::factory()->create();
        $owner = $this->moderatorOf($group, GroupModeratorRole::Owner);

        $this->actingAs($owner)->get(route('groups.moderation', $group->slug))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('role', 'owner')
                ->where('can_edit', true)
                ->has('cases.data', 0));
    }

    public function test_a_moderator_opens_one_case_of_their_group(): void
    {
        $group = Group::factory()->create();
        $moderator = $this->moderatorOf($group);
        $post = Post::factory()->hidden()->create(['group_id' => $group->id, 'title' => 'Reported title']);
        $case = app(ModerationCaseRepositoryInterface::class)->open($group->id, ReportTargetType::Post, $post->id);

        $this->actingAs($moderator)->get(route('groups.moderation.case', [$group->slug, $case->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Groups/ModerationCase')
                ->where('role', 'moderator')
                ->where('moderation_case.id', $case->id)
                ->where('moderation_case.target.title', 'Reported title')
                ->missing('moderation_case.target.author')
                ->missing('moderation_case.target.user_id'));
    }

    public function test_a_case_is_closed_to_guests_members_and_moderators_of_other_groups(): void
    {
        $group = Group::factory()->create();
        $post = Post::factory()->hidden()->create(['group_id' => $group->id]);
        $case = app(ModerationCaseRepositoryInterface::class)->open($group->id, ReportTargetType::Post, $post->id);
        $url = route('groups.moderation.case', [$group->slug, $case->id]);
        $member = User::factory()->create();
        app(GroupRepositoryInterface::class)->subscribe($group->id, $member->id);

        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($member)->get($url)->assertForbidden();
        $this->actingAs($this->moderatorOf(Group::factory()->create()))->get($url)->assertForbidden();
    }

    public function test_a_case_of_another_group_or_a_closed_or_unknown_one_is_not_found(): void
    {
        $group = Group::factory()->create();
        $other = Group::factory()->create();
        $moderator = $this->moderatorOf($group);
        $cases = app(ModerationCaseRepositoryInterface::class);
        $elsewhere = $cases->open($other->id, ReportTargetType::Post, Post::factory()->create(['group_id' => $other->id])->id);
        $closed = $cases->open($group->id, ReportTargetType::Post, Post::factory()->create(['group_id' => $group->id])->id);
        $closed->forceFill(['status' => ModerationCaseStatus::Closed, 'open_key' => null])->save();

        foreach ([$elsewhere->id, $closed->id, (string) Str::uuid()] as $id) {
            $this->actingAs($moderator)->get(route('groups.moderation.case', [$group->slug, $id]))->assertNotFound();
        }
    }

    public function test_the_queue_sends_a_short_start_of_a_long_post_and_the_case_page_the_whole_of_it(): void
    {
        $group = Group::factory()->create();
        $moderator = $this->moderatorOf($group);
        $post = Post::factory()->hidden()->create(['group_id' => $group->id, 'article' => str_repeat('word ', 1000)]);
        $case = app(ModerationCaseRepositoryInterface::class)->open($group->id, ReportTargetType::Post, $post->id);

        $this->actingAs($moderator)->get(route('groups.moderation', $group->slug))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('cases.data.0.id', $case->id)
                ->where('cases.data.0.target.excerpt', fn ($excerpt) => mb_strlen($excerpt) <= 203)
                ->missing('cases.data.0.target.article_html')
                ->missing('cases.data.0.reports'));

        $this->actingAs($moderator)->get(route('groups.moderation.case', [$group->slug, $case->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('moderation_case.target.article_html', fn ($html) => mb_strlen($html) > 4000));
    }
}
