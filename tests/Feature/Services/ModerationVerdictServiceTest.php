<?php

namespace Tests\Feature\Services;

use App\Enums\ModerationCaseStatus;
use App\Enums\ModerationVerdictType;
use App\Enums\ReportTargetType;
use App\Events\ModerationVerdictGiven;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Models\Comment;
use App\Models\Group;
use App\Models\ModerationCase;
use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use App\Services\ModerationVerdictService;
use Database\Seeders\LanguageSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ModerationVerdictServiceTest extends TestCase
{
    use RefreshDatabase;

    private ModerationVerdictService $verdicts;

    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->verdicts = app(ModerationVerdictService::class);
        $this->moderator = User::factory()->create();
    }

    /** A hidden post or comment with an open case that opened with two rules. */
    private function openCase(string $kind = 'post'): ModerationCase
    {
        $group = Group::factory()->create();
        $groups = app(GroupRepositoryInterface::class);
        $version = $groups->addRuleVersion($group->id, [['text' => 'Be kind', 'example' => null], ['text' => 'No spam', 'example' => null]]);

        if ($kind === 'post') {
            $target = Post::factory()->hidden()->create(['group_id' => $group->id, 'article' => 'You are stupid and wrong']);
            $type = ReportTargetType::Post;
        } else {
            $target = Comment::factory()->hidden()->create(['text' => 'You are stupid and wrong']);
            $type = ReportTargetType::Comment;
        }

        return app(ModerationCaseRepositoryInterface::class)->open($group->id, $type, $target->id, $version->id);
    }

    private function marks(): array
    {
        return [['start' => 8, 'end' => 14, 'quote' => 'stupid']];
    }

    public function test_no_violation_shows_the_content_again_and_closes_the_case(): void
    {
        Event::fake([ModerationVerdictGiven::class]);
        $case = $this->openCase();

        $verdict = $this->verdicts->decide($case, $this->moderator->id, ModerationVerdictType::NoViolation);

        $this->assertSame(ModerationVerdictType::NoViolation, $verdict->type);
        $this->assertNull($verdict->rule_index);
        $this->assertNull($verdict->marks);
        $this->assertSame($this->moderator->id, $verdict->decider->id);

        $case->refresh();
        $this->assertSame(ModerationCaseStatus::Closed, $case->status);
        $this->assertNull($case->open_key);
        $this->assertNotNull($case->closed_at);

        $this->assertFalse(Post::find($case->target_id)->isConcealed());
        Event::assertDispatched(ModerationVerdictGiven::class, fn ($event) => $event->verdict->is($verdict));
    }

    public function test_remove_takes_a_post_down_but_keeps_its_text_and_is_not_the_authors_deletion(): void
    {
        $case = $this->openCase();

        $verdict = $this->verdicts->decide($case, $this->moderator->id, ModerationVerdictType::Remove, 0, $this->marks(), '  It is an insult.  ');

        $post = Post::find($case->target_id);
        $this->assertTrue($post->isRemoved());
        $this->assertFalse($post->isHidden());
        $this->assertFalse($post->is_deleted);
        $this->assertSame('You are stupid and wrong', $post->article);

        $this->assertSame('It is an insult.', $verdict->reason);
        $this->assertSame($case->rule_version_id, $verdict->rule_version_id);
        $this->assertSame(0, $verdict->rule_index);
        $this->assertSame('stupid', $verdict->marks[0]['quote']);
    }

    public function test_a_removed_post_and_comment_are_redacted_for_the_page_and_say_they_were_removed(): void
    {
        $postCase = $this->openCase();
        $this->verdicts->decide($postCase, $this->moderator->id, ModerationVerdictType::Remove, 1, $this->marks(), 'Spam.');
        $commentCase = $this->openCase('comment');
        $this->verdicts->decide($commentCase, $this->moderator->id, ModerationVerdictType::Warning, 0, $this->marks(), 'Rude.');

        $post = Post::with(['author', 'group'])->find($postCase->target_id)->setAttribute('upvotes_count', 0)->setAttribute('downvotes_count', 0);
        $postData = (new PostResource($post))->toArray(Request::create('/'));
        $this->assertTrue($postData['is_hidden']);
        $this->assertTrue($postData['is_removed']);
        $this->assertSame('', $postData['article']);
        $this->assertSame('Hidden', $postData['author']['username']);

        $comment = Comment::with('author')->find($commentCase->target_id)->setAttribute('upvotes_count', 0)->setAttribute('downvotes_count', 0);
        $commentData = (new CommentResource($comment))->toArray(Request::create('/'));
        $this->assertTrue($commentData['is_removed']);
        $this->assertSame('', $commentData['text']);
    }

    public function test_removed_content_cannot_be_voted_reported_or_edited(): void
    {
        $case = $this->openCase();
        $this->verdicts->decide($case, $this->moderator->id, ModerationVerdictType::Remove, 0, $this->marks(), 'Rude.');
        $post = Post::find($case->target_id);

        $this->assertFalse(User::find($post->user_id)->can('update', $post));
        $this->assertFalse(User::factory()->create()->can('report', $post));
        $this->assertFalse(User::factory()->create()->can('vote', $post));
    }

    public function test_a_warning_removes_a_comment(): void
    {
        $case = $this->openCase('comment');

        $verdict = $this->verdicts->decide($case, $this->moderator->id, ModerationVerdictType::Warning, 0, $this->marks(), 'Rude.');

        $this->assertTrue(Comment::find($case->target_id)->isRemoved());
    }

    public function test_a_verdict_names_the_rules_as_they_were_when_the_case_opened(): void
    {
        $case = $this->openCase();
        // The owner changes the rules while the case waits: rule 2 is gone.
        app(GroupRepositoryInterface::class)->addRuleVersion($case->group_id, [['text' => 'Only one rule now', 'example' => null]]);

        $verdict = $this->verdicts->decide($case, $this->moderator->id, ModerationVerdictType::Remove, 1, $this->marks(), 'Spam.');

        $this->assertSame($case->rule_version_id, $verdict->rule_version_id);
    }

    #[DataProvider('incompleteVerdicts')]
    public function test_a_punishing_verdict_must_be_complete(ModerationVerdictType $type, ?int $rule, ?array $marks, ?string $reason): void
    {
        $case = $this->openCase();

        try {
            $this->verdicts->decide($case, $this->moderator->id, $type, $rule, $marks, $reason);
            $this->fail('An incomplete verdict was accepted.');
        } catch (InvalidArgumentException) {
            // The case is still open and its content still hidden.
            $this->assertSame(ModerationCaseStatus::Open, $case->fresh()->status);
            $this->assertTrue(Post::find($case->target_id)->isHidden());
        }
    }

    public static function incompleteVerdicts(): array
    {
        $marks = [['start' => 8, 'end' => 14, 'quote' => 'stupid']];

        return [
            'no rule' => [ModerationVerdictType::Remove, null, $marks, 'Rude.'],
            'a rule the group does not have' => [ModerationVerdictType::Remove, 5, $marks, 'Rude.'],
            'no marks' => [ModerationVerdictType::Remove, 0, null, 'Rude.'],
            'empty marks' => [ModerationVerdictType::Remove, 0, [], 'Rude.'],
            'a mark ending before it starts' => [ModerationVerdictType::Remove, 0, [['start' => 9, 'end' => 3, 'quote' => 'x']], 'Rude.'],
            'a mark without text' => [ModerationVerdictType::Remove, 0, [['start' => 1, 'end' => 3, 'quote' => ' ']], 'Rude.'],
            'no reason' => [ModerationVerdictType::Remove, 0, $marks, null],
            'a blank reason' => [ModerationVerdictType::Remove, 0, $marks, '   '],
        ];
    }

    public function test_a_case_somebody_else_just_decided_is_refused(): void
    {
        $case = $this->openCase();
        $this->verdicts->decide($case, $this->moderator->id, ModerationVerdictType::NoViolation);

        // Someone who still holds the case as it was before the first verdict.
        $stale = ModerationCase::find($case->id)->setAttribute('status', ModerationCaseStatus::Open);
        $this->expectException(DomainException::class);

        $this->verdicts->decide($stale, User::factory()->create()->id, ModerationVerdictType::Remove, 0, $this->marks(), 'Rude.');
    }

    public function test_a_closed_case_cannot_be_decided(): void
    {
        $case = $this->openCase();
        $this->verdicts->decide($case, $this->moderator->id, ModerationVerdictType::NoViolation);

        $this->expectException(DomainException::class);

        $this->verdicts->decide($case->fresh(), $this->moderator->id, ModerationVerdictType::NoViolation);
    }

    public function test_what_a_no_violation_verdict_does_not_use_is_dropped(): void
    {
        $verdict = $this->verdicts->decide($this->openCase(), $this->moderator->id, ModerationVerdictType::NoViolation, 0, $this->marks(), null, 10);

        $this->assertNull($verdict->rule_version_id);
        $this->assertNull($verdict->rule_index);
        $this->assertNull($verdict->marks);
    }

    public function test_content_can_get_a_new_case_after_the_old_one_is_closed(): void
    {
        $case = $this->openCase();
        $this->verdicts->decide($case, $this->moderator->id, ModerationVerdictType::NoViolation);

        $again = app(ModerationCaseRepositoryInterface::class)->open($case->group_id, $case->target_type, $case->target_id);

        $this->assertNotSame($case->id, $again->id);
    }
}
