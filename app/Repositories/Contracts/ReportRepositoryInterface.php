<?php

namespace App\Repositories\Contracts;

use App\Enums\ReportTargetType;
use App\Models\Report;

interface ReportRepositoryInterface
{
    public function exists(string $reporterId, ReportTargetType $targetType, string $targetId): bool;

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
