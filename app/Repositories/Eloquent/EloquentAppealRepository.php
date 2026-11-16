<?php

namespace App\Repositories\Eloquent;

use App\Enums\AppealStatus;
use App\Models\Appeal;
use App\Repositories\Contracts\AppealRepositoryInterface;

class EloquentAppealRepository implements AppealRepositoryInterface
{
    public function existsForVerdict(string $verdictId): bool
    {
        return Appeal::where('moderation_verdict_id', $verdictId)->exists();
    }

    public function create(string $verdictId, string $appellantId, string $statement): Appeal
    {
        return Appeal::create([
            'moderation_verdict_id' => $verdictId,
            'appellant_id' => $appellantId,
            'statement' => $statement,
            'status' => AppealStatus::Pending,
        ]);
    }
}
