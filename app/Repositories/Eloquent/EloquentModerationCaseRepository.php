<?php

namespace App\Repositories\Eloquent;

use App\Enums\ModerationCaseStatus;
use App\Enums\ReportTargetType;
use App\Models\ModerationCase;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;

class EloquentModerationCaseRepository implements ModerationCaseRepositoryInterface
{
    public function findOpen(ReportTargetType $targetType, string $targetId): ?ModerationCase
    {
        return ModerationCase::where('open_key', $this->openKey($targetType, $targetId))->first();
    }

    public function open(string $groupId, ReportTargetType $targetType, string $targetId): ModerationCase
    {
        return ModerationCase::create([
            'group_id' => $groupId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'status' => ModerationCaseStatus::Open,
            'open_key' => $this->openKey($targetType, $targetId),
        ]);
    }

    private function openKey(ReportTargetType $targetType, string $targetId): string
    {
        return $targetType->value.':'.$targetId;
    }
}
