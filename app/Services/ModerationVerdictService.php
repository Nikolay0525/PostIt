<?php

namespace App\Services;

use App\Enums\ModerationCaseStatus;
use App\Enums\ModerationVerdictType;
use App\Enums\ReportTargetType;
use App\Events\ModerationVerdictGiven;
use App\Models\ModerationCase;
use App\Models\ModerationVerdict;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use App\Repositories\Contracts\PostRepositoryInterface;
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
        protected CommentRepositoryInterface $commentRepository
    ) {}

    /**
     * Decides a case: stores the verdict, closes the case and does what the verdict says to the
     * content — shows it again, or takes it down.
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

        try {
            $verdict = DB::transaction(function () use ($case, $moderatorId, $type, $ruleIndex, $marks, $reason) {
                $verdict = $this->caseRepository->createVerdict(
                    $case->id,
                    $type,
                    $moderatorId,
                    $type->punishes() ? $case->rule_version_id : null,
                    $ruleIndex,
                    $marks === null ? null : $this->cleanMarks($marks),
                    $reason === '' ? null : $reason,
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

    private function assertComplete(ModerationCase $case, ?int $ruleIndex, ?array $marks, ?string $reason): void
    {
        $rules = $case->ruleVersion?->rules ?? [];

        if ($ruleIndex === null || ! isset($rules[$ruleIndex])) {
            throw new InvalidArgumentException('A verdict that punishes must name a rule of the group.');
        }

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
    }
}
