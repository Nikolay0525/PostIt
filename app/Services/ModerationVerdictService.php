<?php

namespace App\Services;

use App\Enums\ModerationCaseStatus;
use App\Enums\ModerationVerdictType;
use App\Enums\ReportTargetType;
use App\Enums\VoteParentType;
use App\Events\ModerationCasePassedUp;
use App\Events\ModerationVerdictGiven;
use App\Models\ModerationCase;
use App\Models\ModerationPassUp;
use App\Models\ModerationVerdict;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use App\Repositories\Contracts\PlatformRuleRepositoryInterface;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\VoteRepositoryInterface;
use App\Support\Concerns\RendersMarkdown;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ModerationVerdictService
{
    use RendersMarkdown;

    public function __construct(
        protected ModerationCaseRepositoryInterface $caseRepository,
        protected PostRepositoryInterface $postRepository,
        protected CommentRepositoryInterface $commentRepository,
        protected PlatformRuleRepositoryInterface $platformRuleRepository,
        protected ModerationContextService $contextService,
        protected VoteRepositoryInterface $voteRepository,
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Decides a case: stores the verdict, closes the case and does what the verdict says to the
     * content — shows it again, or takes it down. Taken down, it counts as having no votes, and its
     * author loses the karma they earned (takeBackKarma()).
     *
     * A verdict that punishes must name the rule it broke (an index in the rules the case opened
     * with), mark where in the content (a list of {field, start, end, quote}: `field` is "title" or
     * "body", the body if left out; the positions count in that part's plain text) and give the moderator's
     * reason. What a verdict does not use is dropped. How long a warning's strike lasts is not
     * chosen here: StrikeService works it out.
     *
     * @param  list<array{field?: string, start: int, end: int, quote: string}>|null  $marks
     *
     * @throws InvalidArgumentException when the verdict is incomplete
     * @throws DomainException when the case is not open any more, or was just decided by someone else
     */
    public function decide(
        ModerationCase $case,
        string $moderatorId,
        ModerationVerdictType $type,
        ?int $ruleIndex = null,
        ?array $marks = null,
        ?string $reason = null,
    ): ModerationVerdict {
        if ($case->status !== ModerationCaseStatus::Open) {
            throw new DomainException('The case is closed.');
        }

        $reason = $reason === null ? null : trim($reason);

        if ($type->punishes()) {
            $this->assertComplete($case, $ruleIndex, $marks, $reason);
        } else {
            [$ruleIndex, $marks] = [null, null];
        }

        // The conversation as the moderator saw it, kept with a verdict that will be in the log.
        $context = $type->punishes() ? $this->contextService->snapshot($case) : null;

        try {
            $verdict = DB::transaction(function () use ($case, $moderatorId, $type, $ruleIndex, $marks, $reason, $context) {
                $verdict = $this->caseRepository->createVerdict(
                    $case->id,
                    $type,
                    $moderatorId,
                    $type->punishes() ? $case->rule_version_id : null,
                    $ruleIndex,
                    $marks === null ? null : $this->cleanMarks($marks),
                    $reason === '' ? null : $reason,
                    $context,
                );

                $this->caseRepository->close($case->id);
                $this->applyToContent($case, $type);

                return $verdict;
            });
        } catch (UniqueConstraintViolationException) {
            throw new DomainException('The case was decided already.');
        }

        ModerationVerdictGiven::dispatch($verdict);

        return $verdict;
    }

    /**
     * Passes the case up to the platform's administrators: for content that breaks the platform's rules,
     * which a moderator does not punish for. Not a verdict — the case leaves the group's queue and its
     * verdict will be an administrator's — but it carries the same hint a verdict would: the platform
     * rule (an index in the platform's rules in force), where it is broken, and why. The content stays
     * hidden meanwhile.
     *
     * @param  list<array{field?: string, start: int, end: int, quote: string}>  $marks
     *
     * @throws InvalidArgumentException when the hint is incomplete
     * @throws DomainException when the case is not open any more, or was just decided or passed up
     */
    public function passUp(ModerationCase $case, string $moderatorId, ?int $ruleIndex, ?array $marks, ?string $reason): ModerationPassUp
    {
        if ($case->status !== ModerationCaseStatus::Open) {
            throw new DomainException('The case is not open.');
        }

        $platformRules = $this->platformRuleRepository->current();

        if ($ruleIndex === null || ! isset($platformRules?->rules[$ruleIndex])) {
            throw new InvalidArgumentException('A case is passed up under a rule of the platform.');
        }

        $reason = trim((string) $reason);
        $this->assertMarksAndReason($case, $marks, $reason);

        try {
            $passUp = DB::transaction(fn () => $this->caseRepository->passUp(
                $case->id,
                $moderatorId,
                $platformRules->id,
                $ruleIndex,
                $this->cleanMarks($marks),
                $reason,
            ));
        } catch (UniqueConstraintViolationException) {
            throw new DomainException('The case was passed up already.');
        }

        ModerationCasePassedUp::dispatch($passUp);

        return $passUp;
    }

    private function assertComplete(ModerationCase $case, ?int $ruleIndex, ?array $marks, ?string $reason): void
    {
        $rules = $case->ruleVersion?->rules ?? [];

        if ($ruleIndex === null || ! isset($rules[$ruleIndex])) {
            throw new InvalidArgumentException('A verdict that punishes must name a rule of the group.');
        }

        $this->assertMarksAndReason($case, $marks, $reason);
    }

    /**
     * What a punishing verdict and a pass-up both need: the places marked, each quoting the part of the
     * content it says it is in, and the moderator's reason.
     */
    private function assertMarksAndReason(ModerationCase $case, ?array $marks, ?string $reason): void
    {
        if ($marks === null || $marks === []) {
            throw new InvalidArgumentException('A verdict that punishes must mark where the rule was broken.');
        }

        $texts = $this->contentTexts($case);

        foreach ($marks as $mark) {
            $start = $mark['start'] ?? null;
            $end = $mark['end'] ?? null;
            $quote = (string) ($mark['quote'] ?? '');
            $field = $mark['field'] ?? 'body';

            if (! is_int($start) || ! is_int($end) || $start < 0 || $end <= $start || trim($quote) === '') {
                throw new InvalidArgumentException('A mark needs a start, an end after it, and the quoted text.');
            }

            // A post has a title and a body to mark in; a comment only a body.
            if (! array_key_exists($field, $texts)) {
                throw new InvalidArgumentException('A mark is in a part the content does not have.');
            }

            // What is marked must be in what was judged; the positions only say where to look first.
            if ($texts[$field] !== null && ! str_contains($texts[$field], $quote)) {
                throw new InvalidArgumentException('A mark quotes something that is not in the content.');
            }
        }

        if ($reason === null || $reason === '') {
            throw new InvalidArgumentException('A verdict that punishes must give the moderator\'s reason.');
        }
    }

    /**
     * The parts of the content a moderator reads and marks, by name: a post's title and its body as
     * plain text, a comment's body as it is. A part is null if the content is gone (nothing to check
     * against); a part a piece of content does not have is not in the list at all.
     *
     * @return array<string, ?string>
     */
    private function contentTexts(ModerationCase $case): array
    {
        if ($case->target_type === ReportTargetType::Post) {
            $post = $this->postRepository->find($case->target_id);

            return [
                'title' => $post?->title,
                'body' => $post === null ? null : $this->markdownToPlainText($post->article),
            ];
        }

        if ($case->target_type === ReportTargetType::Comment) {
            return ['body' => $this->commentRepository->find($case->target_id)?->text];
        }

        return [];
    }

    /**
     * @param  list<array<string, mixed>>  $marks
     * @return list<array{field: string, start: int, end: int, quote: string}>
     */
    private function cleanMarks(array $marks): array
    {
        return array_map(
            fn (array $mark) => [
                'field' => $mark['field'] ?? 'body',
                'start' => $mark['start'],
                'end' => $mark['end'],
                'quote' => $mark['quote'],
            ],
            array_values($marks)
        );
    }

    private function applyToContent(ModerationCase $case, ModerationVerdictType $type): void
    {
        $remove = $type->punishes();

        match ($case->target_type) {
            ReportTargetType::Post => $remove
                ? $this->postRepository->remove($case->target_id)
                : $this->postRepository->restore($case->target_id),
            ReportTargetType::Comment => $remove
                ? $this->commentRepository->remove($case->target_id)
                : $this->commentRepository->restore($case->target_id),
            ReportTargetType::User => null,
        };

        if ($remove) {
            $this->takeBackKarma($case);
        }
    }

    // Content taken down for breaking a rule earns its author nothing: the karma its votes gave is taken
    // back, from the account and the group alike. Karma is a counter of its own, not worked out from the
    // votes, so it is taken back here. The votes themselves are kept, as they were when it was hidden
    // (PostPolicy::vote froze them), so that an appeal that overturns the verdict brings them back; till
    // then the content counts as having none (the post and comment repositories' withStats()). A loss
    // stays a loss: what downvotes took is not given back, or breaking a rule with a disliked post would pay.
    private function takeBackKarma(ModerationCase $case): void
    {
        $type = match ($case->target_type) {
            ReportTargetType::Post => VoteParentType::Post,
            ReportTargetType::Comment => VoteParentType::Comment,
            ReportTargetType::User => null,
        };
        $authorId = $this->caseRepository->authorIdOf($case);

        if ($type === null || $authorId === null) {
            return;
        }

        $votes = $this->voteRepository->countVotes($case->target_id, $type);
        $earned = $votes['upvotes'] - $votes['downvotes'];

        if ($earned > 0) {
            $this->userRepository->addKarma($authorId, $case->group_id, -$earned);
        }
    }
}
