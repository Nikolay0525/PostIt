<?php

namespace App\Services;

use App\Events\ModerationCaseOpened;
use App\Models\ModerationCase;
use App\Models\Report;
use App\Enums\ReportTargetType;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ModerationCaseService
{
    // The fewest reports that open a case, however quiet the group is.
    public const MIN_REPORTS = 1;

    // How many reports open one in a livelier group: this share of the people who voted in it over
    // the counting window. Nobody sets it for the group, so it cannot be tuned to protect or hit anyone.
    public const SHARE_OF_ACTIVE_VOTERS = 0.05;

    public function __construct(
        protected ModerationCaseRepositoryInterface $caseRepository,
        protected ReportRepositoryInterface $reportRepository,
        protected GroupRepositoryInterface $groupRepository,
        protected PostRepositoryInterface $postRepository,
        protected CommentRepositoryInterface $commentRepository
    ) {}

    /**
     * How many reports from members with some reputation open a case in the group. Read from the
     * group's last count of its activity (GroupActivity); a group not counted yet has none, so the
     * minimum applies.
     */
    public function reportsNeeded(string $groupId): int
    {
        $voters = $this->groupRepository->findActivity($groupId)?->voters_count ?? 0;

        return max(self::MIN_REPORTS, (int) ceil($voters * self::SHARE_OF_ACTIVE_VOTERS));
    }

    /**
     * Opens a case on the reported post or comment when there are enough reports on it and no case
     * is open on it yet; the reports that count become the case's. Null when nothing was opened.
     */
    public function openIfReported(Report $report): ?ModerationCase
    {
        $type = $report->target_type;
        $targetId = $report->target_id;

        if ($this->caseRepository->findOpen($type, $targetId) !== null) {
            return null;
        }

        if ($this->reportRepository->countReputable($type, $targetId, $report->group_id) < $this->reportsNeeded($report->group_id)) {
            return null;
        }

        try {
            $case = DB::transaction(function () use ($report, $type, $targetId) {
                $case = $this->caseRepository->open($report->group_id, $type, $targetId, $this->groupRepository->find($report->group_id)?->currentRuleVersion?->id);
                $this->reportRepository->attachToCase($type, $targetId, $case->id);

                return $case;
            });
        } catch (UniqueConstraintViolationException) {
            // Another report tipped the count at the same moment and opened it first.
            return null;
        }

        ModerationCaseOpened::dispatch($case);

        return $case;
    }

    /**
     * A case being open means its content is hidden until a decision is made. Hiding is not a
     * punishment: nothing is decided yet.
     */
    public function hideContentOf(ModerationCase $case): void
    {
        match ($case->target_type) {
            ReportTargetType::Post => $this->postRepository->hide($case->target_id),
            ReportTargetType::Comment => $this->commentRepository->hide($case->target_id),
            ReportTargetType::User => null,
        };
    }
}
