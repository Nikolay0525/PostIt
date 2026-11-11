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
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ModerationVerdictService
{
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
     * with), mark where in the content (a list of {start, end, quote}) and give the moderator's
     * reason. What a verdict does not use is dropped. How long a warning's strike lasts is not
     * chosen here: StrikeService works it out.
     *
     * @param  list<array{start: int, end: int, quote: string}>|null  $marks
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

        foreach ($marks as $mark) {
            $start = $mark['start'] ?? null;
            $end = $mark['end'] ?? null;

            if (! is_int($start) || ! is_int($end) || $start < 0 || $end <= $start || trim((string) ($mark['quote'] ?? '')) === '') {
                throw new InvalidArgumentException('A mark needs a start, an end after it, and the quoted text.');
            }
        }

        if ($reason === null || $reason === '') {
            throw new InvalidArgumentException('A verdict that punishes must give the moderator\'s reason.');
        }
    }

    /**
     * @param  list<array<string, mixed>>  $marks
     * @return list<array{start: int, end: int, quote: string}>
     */
    private function cleanMarks(array $marks): array
    {
        return array_map(
            fn (array $mark) => ['start' => $mark['start'], 'end' => $mark['end'], 'quote' => $mark['quote']],
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
