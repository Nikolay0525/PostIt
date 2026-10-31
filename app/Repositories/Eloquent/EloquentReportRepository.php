<?php

namespace App\Repositories\Eloquent;

use App\Enums\ReportStatus;
use App\Enums\ReportTargetType;
use App\Models\Report;
use App\Repositories\Contracts\ReportRepositoryInterface;

class EloquentReportRepository implements ReportRepositoryInterface
{
    public function exists(string $reporterId, ReportTargetType $targetType, string $targetId): bool
    {
        return Report::where('reporter_id', $reporterId)
            ->where('target_type', $targetType->value)
            ->where('target_id', $targetId)
            ->exists();
    }

    public function create(
        string $reporterId,
        ReportTargetType $targetType,
        string $targetId,
        string $groupId,
        ?string $ruleVersionId,
        ?int $ruleIndex,
        bool $breaksPlatformRules,
        ?string $text,
    ): Report {
        return Report::create([
            'reporter_id' => $reporterId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'group_id' => $groupId,
            'rule_version_id' => $ruleVersionId,
            'rule_index' => $ruleIndex,
            'breaks_platform_rules' => $breaksPlatformRules,
            'text' => $text,
            'status' => ReportStatus::Pending,
        ]);
    }
}
