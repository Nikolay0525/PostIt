<?php

namespace Tests\Feature\Http;

use App\Enums\GroupModeratorRole;
use App\Enums\ReportTargetType;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
