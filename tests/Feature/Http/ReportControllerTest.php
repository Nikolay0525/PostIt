<?php

namespace Tests\Feature\Http;

use App\Enums\ReportStatus;
use App\Enums\ReportTargetType;
use App\Http\Requests\StoreReportRequest;
use App\Models\Comment;
use App\Models\Group;
use App\Models\GroupRuleVersion;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // UserObserver assigns a default UI/speaking language on user creation.
        $this->seed(LanguageSeeder::class);
    }

    private function reportsSent(User $user): int
    {
        return (int) $user->counters()->value('reports_sent');
    }

    public function test_a_user_can_report_a_post(): void
    {
        $reporter = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($reporter)
            ->postJson('/reports', ['target_type' => 'post', 'target_id' => $post->id, 'text' => 'Spam'])
            ->assertCreated()
            ->assertJson(['reported' => true]);

        $report = Report::firstOrFail();
        $this->assertSame($reporter->id, $report->reporter_id);
        $this->assertSame(ReportTargetType::Post, $report->target_type);
        $this->assertSame($post->id, $report->target_id);
        $this->assertSame($post->group_id, $report->group_id);
        $this->assertSame('Spam', $report->text);
        $this->assertSame(ReportStatus::Pending, $report->status);
        $this->assertSame(1, $this->reportsSent($reporter));
    }

    private function groupWithRules(): Group
    {
        $group = Group::factory()->create();
        GroupRuleVersion::create(['group_id' => $group->id, 'rules' => [['text' => 'No spam.', 'example' => null]]]);
        $this->version = GroupRuleVersion::create(['group_id' => $group->id, 'rules' => [
            ['text' => 'No piracy links.', 'example' => 'ROM sites'],
            ['text' => 'Be kind.', 'example' => null],
        ]]);

        return $group;
    }

    private GroupRuleVersion $version;

    public function test_a_report_names_the_broken_rule_of_the_current_version(): void
    {
        $reporter = User::factory()->create();
        $post = Post::factory()->create(['group_id' => $this->groupWithRules()->id]);

        $this->actingAs($reporter)
            ->postJson('/reports', ['target_type' => 'post', 'target_id' => $post->id, 'rule_index' => 1])
            ->assertCreated();

        $report = Report::firstOrFail();
        $this->assertSame($this->version->id, $report->rule_version_id);
        $this->assertSame(1, $report->rule_index);
        $this->assertNull($report->text);
        $this->assertSame('Be kind.', $report->ruleVersion->rules[$report->rule_index]['text']);
    }

    public function test_something_else_needs_a_reason_and_names_no_rule(): void
    {
        $reporter = User::factory()->create();
        $post = Post::factory()->create(['group_id' => $this->groupWithRules()->id]);

        $this->actingAs($reporter)
            ->postJson('/reports', ['target_type' => 'post', 'target_id' => $post->id])
            ->assertUnprocessable()->assertJsonValidationErrors('text');

        $this->actingAs($reporter)
            ->postJson('/reports', ['target_type' => 'post', 'target_id' => $post->id, 'text' => 'Doxxing'])
            ->assertCreated();

        $report = Report::firstOrFail();
        $this->assertNull($report->rule_version_id);
        $this->assertNull($report->rule_index);
        $this->assertSame('Doxxing', $report->text);
    }

    public function test_a_report_can_say_the_platforms_rules_are_broken(): void
    {
        $reporter = User::factory()->create();
        $post = Post::factory()->create(['group_id' => $this->groupWithRules()->id]);

        $this->actingAs($reporter)
            ->postJson('/reports', ['target_type' => 'post', 'target_id' => $post->id, 'platform_violation' => true])
            ->assertCreated();

        $report = Report::firstOrFail();
        $this->assertTrue($report->breaks_platform_rules);
        $this->assertNull($report->rule_version_id);
        $this->assertNull($report->rule_index);
        $this->assertNull($report->text);
    }

    public function test_the_platforms_rules_and_a_group_rule_are_not_both_named(): void
    {
        $reporter = User::factory()->create();
        $post = Post::factory()->create(['group_id' => $this->groupWithRules()->id]);

        $this->actingAs($reporter)
            ->postJson('/reports', ['target_type' => 'post', 'target_id' => $post->id, 'platform_violation' => true, 'rule_index' => 0])
            ->assertUnprocessable()->assertJsonValidationErrors('rule_index');

        $this->assertSame(0, Report::count());
    }

    public function test_a_rule_the_group_does_not_have_is_rejected(): void
    {
        $reporter = User::factory()->create();
        $withRules = Post::factory()->create(['group_id' => $this->groupWithRules()->id]);
        $withoutRules = Post::factory()->create();

        $this->actingAs($reporter)
            ->postJson('/reports', ['target_type' => 'post', 'target_id' => $withRules->id, 'rule_index' => 2])
            ->assertUnprocessable()->assertJsonValidationErrors('rule_index');
        $this->actingAs($reporter)
            ->postJson('/reports', ['target_type' => 'post', 'target_id' => $withoutRules->id, 'rule_index' => 0])
            ->assertUnprocessable()->assertJsonValidationErrors('rule_index');

        $this->assertSame(0, Report::count());
        $this->assertSame(0, $this->reportsSent($reporter));
    }

    public function test_the_report_form_gets_the_groups_current_rules(): void
    {
        $group = $this->groupWithRules();

        $this->actingAs(User::factory()->create())->getJson("/groups/{$group->id}/rules")
            ->assertOk()
            ->assertJson(['rules' => [
                ['text' => 'No piracy links.', 'example' => 'ROM sites'],
                ['text' => 'Be kind.', 'example' => null],
            ]]);

        $this->actingAs(User::factory()->create())->getJson('/groups/'.fake()->uuid().'/rules')->assertNotFound();
    }

    public function test_a_report_on_a_comment_belongs_to_the_posts_group(): void
    {
        $reporter = User::factory()->create();
        $comment = Comment::factory()->create();

        $this->actingAs($reporter)
            ->postJson('/reports', ['target_type' => 'comment', 'target_id' => $comment->id, 'text' => 'Rude'])
            ->assertCreated();

        $report = Report::firstOrFail();
        $this->assertSame(ReportTargetType::Comment, $report->target_type);
        $this->assertSame($comment->post->group_id, $report->group_id);
    }

    public function test_the_same_person_reports_a_piece_of_content_once(): void
    {
        $reporter = User::factory()->create();
        $post = Post::factory()->create();
        $payload = ['target_type' => 'post', 'target_id' => $post->id, 'text' => 'Spam'];

        $this->actingAs($reporter)->postJson('/reports', $payload)->assertCreated();
        $this->actingAs($reporter)->postJson('/reports', $payload)->assertStatus(409)->assertJsonStructure(['message']);

        $this->assertSame(1, Report::count());
        $this->assertSame(1, $this->reportsSent($reporter));
    }

    public function test_different_people_can_report_the_same_content(): void
    {
        $post = Post::factory()->create();
        $payload = ['target_type' => 'post', 'target_id' => $post->id, 'text' => 'Spam'];

        $this->actingAs(User::factory()->create())->postJson('/reports', $payload)->assertCreated();
        $this->actingAs(User::factory()->create())->postJson('/reports', $payload)->assertCreated();

        $this->assertSame(2, Report::count());
    }

    public function test_one_cannot_report_ones_own_content(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $comment = Comment::factory()->create(['user_id' => $author->id]);

        $this->actingAs($author)
            ->postJson('/reports', ['target_type' => 'post', 'target_id' => $post->id, 'text' => 'x'])->assertForbidden();
        $this->actingAs($author)
            ->postJson('/reports', ['target_type' => 'comment', 'target_id' => $comment->id, 'text' => 'x'])->assertForbidden();

        $this->assertSame(0, Report::count());
    }

    public function test_deleted_or_unseen_or_unknown_content_cannot_be_reported(): void
    {
        $reporter = User::factory()->create();
        $deleted = Post::factory()->create(['is_deleted' => true]);
        $private = Post::factory()->create(['group_id' => \App\Models\Group::factory()->private()->create()->id]);

        $this->actingAs($reporter)
            ->postJson('/reports', ['target_type' => 'post', 'target_id' => $deleted->id, 'text' => 'x'])->assertNotFound();
        $this->actingAs($reporter)
            ->postJson('/reports', ['target_type' => 'post', 'target_id' => $private->id, 'text' => 'x'])->assertNotFound();
        $this->actingAs($reporter)
            ->postJson('/reports', ['target_type' => 'post', 'target_id' => fake()->uuid(), 'text' => 'x'])->assertNotFound();

        $this->assertSame(0, Report::count());
    }

    public function test_an_unverified_user_cannot_report(): void
    {
        $post = Post::factory()->create();

        $this->actingAs(User::factory()->unverified()->create())
            ->postJson('/reports', ['target_type' => 'post', 'target_id' => $post->id, 'text' => 'x'])->assertForbidden();
    }

    public function test_a_guest_cannot_report(): void
    {
        $post = Post::factory()->create();

        $this->postJson('/reports', ['target_type' => 'post', 'target_id' => $post->id, 'text' => 'x'])->assertUnauthorized();
    }

    public function test_a_report_needs_a_short_reason_and_a_known_kind(): void
    {
        $reporter = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($reporter)
            ->postJson('/reports', ['target_type' => 'post', 'target_id' => $post->id, 'text' => ''])
            ->assertUnprocessable()->assertJsonValidationErrors('text');
        $this->actingAs($reporter)
            ->postJson('/reports', ['target_type' => 'post', 'target_id' => $post->id, 'text' => str_repeat('x', StoreReportRequest::TEXT_MAX_LENGTH + 1)])
            ->assertUnprocessable()->assertJsonValidationErrors('text');
        $this->actingAs($reporter)
            ->postJson('/reports', ['target_type' => 'user', 'target_id' => $post->id, 'text' => 'x'])
            ->assertUnprocessable()->assertJsonValidationErrors('target_type');

        $this->assertSame(0, Report::count());
    }
}
