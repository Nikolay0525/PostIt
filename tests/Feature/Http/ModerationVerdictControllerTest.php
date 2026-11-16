<?php

namespace Tests\Feature\Http;

use App\Enums\GroupModeratorRole;
use App\Enums\ModerationCaseStatus;
use App\Enums\ModerationVerdictType;
use App\Enums\ReportTargetType;
use App\Models\Comment;
use App\Models\Group;
use App\Models\ModerationCase;
use App\Models\ModerationVerdict;
use App\Models\Post;
use App\Models\Strike;
use App\Models\User;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * A moderator gives the verdict on the case's own page: the form posts here, and they are sent back
 * to the queue.
 */
class ModerationVerdictControllerTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private User $moderator;

    private Post $post;

    private ModerationCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->group = Group::factory()->create();
        $groups = app(GroupRepositoryInterface::class);
        $version = $groups->addRuleVersion($this->group->id, [
            ['text' => 'Be respectful', 'example' => null, 'kind' => 'mute'],
            ['text' => 'No threats', 'example' => null, 'kind' => 'ban'],
        ]);
        $this->moderator = User::factory()->create();
        $groups->addModerator($this->group->id, $this->moderator->id, GroupModeratorRole::Moderator);

        $this->post = Post::factory()->hidden()->create(['group_id' => $this->group->id, 'article' => 'You are a **complete idiot**, honestly']);
        $this->case = app(ModerationCaseRepositoryInterface::class)->open($this->group->id, ReportTargetType::Post, $this->post->id, $version->id);
    }

    private function verdict(array $data, ?User $by = null)
    {
        return $this->actingAs($by ?? $this->moderator)
            ->post(route('groups.moderation.verdict', [$this->group->slug, $this->case->id]), $data);
    }

    private function warning(array $override = []): array
    {
        return [
            'type' => 'warning',
            'rule_index' => 0,
            // Counted in the plain text the moderator reads: the Markdown stars are gone.
            'marks' => [['start' => 10, 'end' => 24, 'quote' => 'complete idiot']],
            'reason' => 'An insult instead of an argument.',
            ...$override,
        ];
    }

    public function test_a_warning_takes_the_post_down_gives_a_strike_and_goes_back_to_the_queue(): void
    {
        $this->verdict($this->warning())
            ->assertRedirect(route('groups.moderation', $this->group->slug))
            ->assertSessionHas('status', 'verdict-given');

        $verdict = ModerationVerdict::sole();
        $this->assertSame(ModerationVerdictType::Warning, $verdict->type);
        $this->assertSame($this->moderator->id, $verdict->decided_by);
        $this->assertSame('complete idiot', $verdict->marks[0]['quote']);
        $this->assertSame(ModerationCaseStatus::Closed, $this->case->fresh()->status);
        $this->assertTrue($this->post->fresh()->isRemoved());
        $this->assertSame(1, Strike::where('user_id', $this->post->user_id)->count());
    }

    public function test_no_violation_needs_nothing_more_and_drops_what_the_form_still_sent(): void
    {
        $this->verdict(['type' => 'no_violation', 'rule_index' => 1, 'marks' => [['start' => 0, 'end' => 3, 'quote' => 'You']], 'reason' => ''])
            ->assertRedirect(route('groups.moderation', $this->group->slug));

        $verdict = ModerationVerdict::sole();
        $this->assertSame(ModerationVerdictType::NoViolation, $verdict->type);
        $this->assertNull($verdict->rule_index);
        $this->assertNull($verdict->marks);
        $this->assertFalse($this->post->fresh()->isConcealed());
    }

    public function test_a_punishing_verdict_needs_a_rule_marks_and_a_reason(): void
    {
        $this->verdict($this->warning(['rule_index' => null]))->assertSessionHasErrors('rule_index');
        $this->verdict($this->warning(['marks' => []]))->assertSessionHasErrors('marks');
        $this->verdict($this->warning(['reason' => '']))->assertSessionHasErrors('reason');
        $this->verdict($this->warning(['reason' => str_repeat('a', 1001)]))->assertSessionHasErrors('reason');
        $this->verdict($this->warning(['type' => 'ban_everyone']))->assertSessionHasErrors('type');

        $this->assertSame(0, ModerationVerdict::count());
        $this->assertSame(ModerationCaseStatus::Open, $this->case->fresh()->status);
    }

    public function test_the_rule_must_be_the_groups_and_the_marked_words_must_be_in_the_text(): void
    {
        $this->verdict($this->warning(['rule_index' => 7]))->assertSessionHasErrors('rule_index');
        $this->verdict($this->warning(['marks' => [['start' => 0, 'end' => 5, 'quote' => 'never written']]]))->assertSessionHasErrors('marks');

        $this->assertSame(0, ModerationVerdict::count());
    }

    public function test_a_comment_is_marked_in_its_own_text(): void
    {
        $comment = Comment::factory()->hidden()->create(['post_id' => Post::factory()->create(['group_id' => $this->group->id])->id, 'text' => 'Nobody asked you, complete idiot.']);
        $case = app(ModerationCaseRepositoryInterface::class)->open($this->group->id, ReportTargetType::Comment, $comment->id, $this->case->rule_version_id);

        $this->actingAs($this->moderator)
            ->post(route('groups.moderation.verdict', [$this->group->slug, $case->id]), $this->warning([
                'type' => 'remove',
                'marks' => [['start' => 18, 'end' => 32, 'quote' => 'complete idiot']],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertTrue($comment->fresh()->isRemoved());
    }

    public function test_only_this_groups_moderators_decide_and_a_case_of_another_group_is_not_found(): void
    {
        $this->verdict($this->warning(), User::factory()->create())->assertForbidden();

        $elsewhere = User::factory()->create();
        app(GroupRepositoryInterface::class)->addModerator(Group::factory()->create()->id, $elsewhere->id, GroupModeratorRole::Moderator);
        $this->verdict($this->warning(), $elsewhere)->assertForbidden();

        $other = Group::factory()->create();
        app(GroupRepositoryInterface::class)->addModerator($other->id, $this->moderator->id, GroupModeratorRole::Moderator);
        $this->actingAs($this->moderator)
            ->post(route('groups.moderation.verdict', [$other->slug, $this->case->id]), $this->warning())
            ->assertNotFound();

        $this->assertSame(0, ModerationVerdict::count());
    }

    public function test_a_case_already_decided_is_closed_to_a_second_verdict(): void
    {
        $this->verdict($this->warning())->assertRedirect();

        // The page was still open for someone else: the case is now closed, so not found.
        $this->verdict(['type' => 'no_violation'])->assertNotFound();
        $this->assertSame(1, ModerationVerdict::count());
    }

    public function test_the_page_offers_the_rules_as_they_were_and_starts_with_the_one_most_reports_named(): void
    {
        $reports = app(ReportRepositoryInterface::class);
        foreach ([1, 1, 0] as $index) {
            $reports->create(User::factory()->create()->id, ReportTargetType::Post, $this->post->id, $this->group->id, $this->case->rule_version_id, $index, false, null);
        }
        $reports->attachToCase(ReportTargetType::Post, $this->post->id, $this->case->id);

        $this->actingAs($this->moderator)->get(route('groups.moderation.case', [$this->group->slug, $this->case->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('rules.0.text', 'Be respectful')
                ->where('rules.0.kind', 'mute')
                ->where('rules.1.kind', 'ban')
                ->where('suggested_rule', 1)
                ->where('context.post.article_text', 'You are a complete idiot, honestly'));
    }

    public function test_a_group_without_rules_offers_none(): void
    {
        $bare = Group::factory()->create();
        app(GroupRepositoryInterface::class)->addModerator($bare->id, $this->moderator->id, GroupModeratorRole::Moderator);
        $post = Post::factory()->hidden()->create(['group_id' => $bare->id]);
        $case = app(ModerationCaseRepositoryInterface::class)->open($bare->id, ReportTargetType::Post, $post->id);

        $this->actingAs($this->moderator)->get(route('groups.moderation.case', [$bare->slug, $case->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('rules', [])->where('suggested_rule', null));

        $this->actingAs($this->moderator)
            ->post(route('groups.moderation.verdict', [$bare->slug, $case->id]), $this->warning())
            ->assertSessionHasErrors('rule_index');
    }

    public function test_a_mark_can_be_in_the_title_of_a_post(): void
    {
        $this->post->update(['title' => 'Idiots only, please']);

        $this->verdict($this->warning(['marks' => [
            ['field' => 'title', 'start' => 0, 'end' => 6, 'quote' => 'Idiots'],
            ['start' => 10, 'end' => 24, 'quote' => 'complete idiot'],
        ]]))->assertSessionHasNoErrors();

        $marks = ModerationVerdict::sole()->marks;
        $this->assertSame('title', $marks[0]['field']);
        $this->assertSame('Idiots', $marks[0]['quote']);
        // Left out, the part is the body.
        $this->assertSame('body', $marks[1]['field']);

        // The author is shown where each mark is.
        $this->actingAs(User::find($this->post->user_id))->get(route('posts.show', [$this->group->slug, $this->post->slug]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('post.moderation.verdict.marks.0.field', 'title')
                ->where('post.moderation.verdict.marks.1.field', 'body'));
    }

    public function test_a_mark_must_be_in_the_part_it_says(): void
    {
        // In the body, not in the title.
        $this->verdict($this->warning(['marks' => [['field' => 'title', 'start' => 10, 'end' => 24, 'quote' => 'complete idiot']]]))
            ->assertSessionHasErrors('marks');
        $this->verdict($this->warning(['marks' => [['field' => 'footer', 'start' => 0, 'end' => 3, 'quote' => 'You']]]))
            ->assertSessionHasErrors('marks.0.field');

        $this->assertSame(0, ModerationVerdict::count());
    }

    public function test_a_comment_has_no_title_to_mark(): void
    {
        $comment = Comment::factory()->hidden()->create(['post_id' => Post::factory()->create(['group_id' => $this->group->id, 'title' => 'Idiots'])->id, 'text' => 'complete idiot']);
        $case = app(ModerationCaseRepositoryInterface::class)->open($this->group->id, ReportTargetType::Comment, $comment->id, $this->case->rule_version_id);

        $this->actingAs($this->moderator)
            ->post(route('groups.moderation.verdict', [$this->group->slug, $case->id]), $this->warning([
                'marks' => [['field' => 'title', 'start' => 0, 'end' => 6, 'quote' => 'Idiots']],
            ]))
            ->assertSessionHasErrors('marks');
    }

    private function passUp(array $override = []): array
    {
        return $this->warning(['type' => 'pass_up', 'rule_index' => 0, ...$override]);
    }

    public function test_passing_up_hands_the_case_to_the_administrators_without_a_verdict(): void
    {
        $this->verdict($this->passUp())
            ->assertRedirect(route('groups.moderation', $this->group->slug))
            ->assertSessionHas('status', 'case-passed-up');

        $case = $this->case->fresh();
        $this->assertSame(ModerationCaseStatus::PassedUp, $case->status);
        $this->assertSame(0, ModerationVerdict::count());

        $passUp = $case->passUp;
        $this->assertSame($this->moderator->id, $passUp->passed_up_by);
        $this->assertSame(\App\Models\PlatformRuleVersion::sole()->id, $passUp->platform_rule_version_id);
        $this->assertSame(0, $passUp->rule_index);
        $this->assertSame('complete idiot', $passUp->marks[0]['quote']);
        $this->assertSame('An insult instead of an argument.', $passUp->reason);
        $this->assertArrayNotHasKey('passed_up_by', $passUp->toArray());

        // Nobody is punished, and the content stays hidden.
        $this->assertTrue($this->post->fresh()->isHidden());
        $this->assertSame(0, Strike::count());
    }

    public function test_a_passed_up_case_leaves_the_groups_queue_and_cannot_be_decided_there(): void
    {
        $this->verdict($this->passUp());

        $this->assertSame(0, app(ModerationCaseRepositoryInterface::class)->openInGroup($this->group->id, 10)->total());
        $this->actingAs($this->moderator)->get(route('groups.moderation.case', [$this->group->slug, $this->case->id]))->assertNotFound();
        $this->verdict($this->warning())->assertNotFound();
    }

    public function test_it_is_passed_up_under_a_rule_of_the_platform_with_marks_and_a_reason(): void
    {
        $this->verdict($this->passUp(['rule_index' => 5]))->assertSessionHasErrors('rule_index');
        $this->verdict($this->passUp(['marks' => []]))->assertSessionHasErrors('marks');
        $this->verdict($this->passUp(['reason' => '']))->assertSessionHasErrors('reason');

        $this->assertSame(ModerationCaseStatus::Open, $this->case->fresh()->status);
    }

    public function test_a_group_without_rules_can_still_pass_a_case_up(): void
    {
        $bare = Group::factory()->create();
        app(GroupRepositoryInterface::class)->addModerator($bare->id, $this->moderator->id, GroupModeratorRole::Moderator);
        $post = Post::factory()->hidden()->create(['group_id' => $bare->id, 'article' => 'You are a complete idiot']);
        $case = app(ModerationCaseRepositoryInterface::class)->open($bare->id, ReportTargetType::Post, $post->id);

        $this->actingAs($this->moderator)
            ->post(route('groups.moderation.verdict', [$bare->slug, $case->id]), $this->passUp([
                'marks' => [['start' => 10, 'end' => 24, 'quote' => 'complete idiot']],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(ModerationCaseStatus::PassedUp, $case->fresh()->status);
    }

    public function test_the_page_offers_the_platforms_rules_in_the_readers_language(): void
    {
        $this->actingAs($this->moderator)->get(route('groups.moderation.case', [$this->group->slug, $this->case->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('platform_rules', 1)
                ->where('platform_rules.0.kind', 'ban')
                ->where('platform_rules.0.text', fn (string $text) => str_starts_with($text, app()->getLocale() === 'uk' ? 'Заборонений вміст' : 'Forbidden content')));
    }
}
