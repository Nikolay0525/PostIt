<?php

namespace App\Repositories\Contracts;

use App\Enums\ReportTargetType;
use App\Models\Report;

interface ReportRepositoryInterface
{
    public function exists(string $reporterId, ReportTargetType $targetType, string $targetId): bool;

    /**
     * How many reports on this post or comment count towards opening a case: those not yet
     * attached to one, from members with some reputation in the group (sub-karma above zero) —
     * so bots and fresh accounts cannot report content away.
     */
    public function countReputable(ReportTargetType $targetType, string $targetId, string $groupId): int;

    /**
     * Attaches the reports on this post or comment that are not part of a case yet to the case.
     */
    public function attachToCase(ReportTargetType $targetType, string $targetId, string $caseId): void;

    /**
     * A new, pending report. $groupId is the group the content is in. What it says was broken is
     * one of the group's rules ($ruleVersionId, $ruleIndex), or the platform's ($breaksPlatformRules),
     * or neither: "something else", and then the reason is $text.
     */
    public function create(
        string $reporterId,
        ReportTargetType $targetType,
        string $targetId,
        string $groupId,
        ?string $ruleVersionId,
        ?int $ruleIndex,
        bool $breaksPlatformRules,
        ?string $text,
    ): Report;
}
