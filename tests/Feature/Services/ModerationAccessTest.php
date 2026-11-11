<?php

namespace Tests\Feature\Services;

use App\Enums\GroupModeratorRole;
use App\Enums\ReportTargetType;
use App\Http\Resources\ModerationCaseResource;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ModerationAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_the_owner_and_the_moderators_of_the_group_may_moderate_it_and_nobody_else(): void
    {
        $group = Group::factory()->create();
        [$owner, $moderator, $member, $elsewhere] = User::factory()->count(4)->create();
        $groups = app(GroupRepositoryInterface::class);
        $groups->addModerator($group->id, $owner->id, GroupModeratorRole::Owner);
        $groups->addModerator($group->id, $moderator->id, GroupModeratorRole::Moderator);
        $groups->subscribe($group->id, $member->id);
        $groups->addModerator(Group::factory()->create()->id, $elsewhere->id, GroupModeratorRole::Owner);

        $this->assertTrue($owner->can('moderate', $group));
        $this->assertTrue($moderator->can('moderate', $group));
        $this->assertFalse($member->can('moderate', $group));
        $this->assertFalse($elsewhere->can('moderate', $group));
    }

    public function test_a_case_shows_the_moderator_the_hidden_text_the_rule_as_it_was_and_nobody_who_wrote_or_reported(): void
    {
        $group = Group::factory()->create();
        $versions = app(GroupRepositoryInterface::class);
        $version = $versions->addRuleVersion($group->id, [['text' => 'Be kind', 'example' => null], ['text' => 'No spam', 'example' => null]]);
        $post = Post::factory()->hidden()->create(['group_id' => $group->id, 'title' => 'Rude title', 'article' => 'Rude **text**']);
        $case = app(ModerationCaseRepositoryInterface::class)->open($group->id, ReportTargetType::Post, $post->id);
        $reports = app(ReportRepositoryInterface::class);
        $reports->create(User::factory()->create()->id, ReportTargetType::Post, $post->id, $group->id, $version->id, 1, false, null);
        $reports->create(User::factory()->create()->id, ReportTargetType::Post, $post->id, $group->id, null, null, true, null);
        $reports->create(User::factory()->create()->id, ReportTargetType::Post, $post->id, $group->id, null, null, false, 'Other reason');
        $reports->attachToCase(ReportTargetType::Post, $post->id, $case->id);

        $resource = new ModerationCaseResource(app(ModerationCaseRepositoryInterface::class)->findForModeration($case->id));
        $json = $resource->toResponse(Request::create('/'))->getContent();
        $data = json_decode($json, true);
        $reportsData = collect($data['reports']);

        $this->assertSame('Rude title', $data['target']['title']);
        $this->assertStringContainsString('<strong>text</strong>', $data['target']['article_html']);
        $this->assertSame(3, $data['reports_count']);
        $this->assertEqualsCanonicalizing(['group_rule', 'platform', 'other'], $reportsData->pluck('kind')->all());
        $this->assertSame('No spam', $reportsData->firstWhere('kind', 'group_rule')['rule']['text']);
        $this->assertSame('Other reason', $reportsData->firstWhere('kind', 'other')['text']);

        $this->assertStringNotContainsString($post->user_id, $json);
        $this->assertStringNotContainsString('reporter', $json);
    }

    public function test_the_moderation_page_has_a_route(): void
    {
        $this->assertSame('/groups/some-group/-/moderation', route('groups.moderation', 'some-group', false));
    }
}
