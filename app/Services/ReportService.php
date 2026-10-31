<?php

namespace App\Services;

use App\Enums\ReportTargetType;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportService
{
    public function __construct(
        protected ReportRepositoryInterface $reportRepository,
        protected UserRepositoryInterface $userRepository,
        protected GroupRepositoryInterface $groupRepository
    ) {}

    /**
     * Files a report on a post or comment and counts it among the reporter's. It says what was
     * broken: $ruleIndex, a rule of the group's current rules; or the platform's rules
     * ($breaksPlatformRules); or, with neither, "something else", and the reason is $text. A person
     * reports a piece of content once: false when they already had, and nothing is stored then.
     *
     * @throws ValidationException when $ruleIndex is not one of the group's rules, is given along with the
     *                             platform's rules, or "something else" has no reason
     */
    public function report(string $reporterId, ReportTargetType $targetType, string $targetId, string $groupId, ?int $ruleIndex, bool $breaksPlatformRules, ?string $text): bool
    {
        $ruleVersion = $this->groupRepository->find($groupId)?->currentRuleVersion;

        if ($ruleIndex !== null && ($breaksPlatformRules || ! isset($ruleVersion?->rules[$ruleIndex]))) {
            throw ValidationException::withMessages(['rule_index' => __('reports.unknown_rule')]);
        }

        if ($ruleIndex === null && ! $breaksPlatformRules && blank($text)) {
            throw ValidationException::withMessages(['text' => __('reports.reason_needed')]);
        }

        if ($this->reportRepository->exists($reporterId, $targetType, $targetId)) {
            return false;
        }

        try {
            DB::transaction(function () use ($reporterId, $targetType, $targetId, $groupId, $ruleVersion, $ruleIndex, $breaksPlatformRules, $text) {
                $this->reportRepository->create(
                    $reporterId,
                    $targetType,
                    $targetId,
                    $groupId,
                    $ruleIndex === null ? null : $ruleVersion->id,
                    $ruleIndex,
                    $breaksPlatformRules,
                    $text,
                );
                $this->userRepository->incrementReportsSent($reporterId);
            });
        } catch (UniqueConstraintViolationException) {
            // The same person reported it twice at once; the unique index let only one through.
            return false;
        }

        return true;
    }
}
