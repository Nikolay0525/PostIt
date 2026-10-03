<?php

namespace App\Repositories\Eloquent;

use App\Models\Achievement;
use App\Repositories\Contracts\AchievementRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentAchievementRepository implements AchievementRepositoryInterface
{
    // `is_completed` never reverts, so the progress row's updated_at is when it was unlocked.
    public function unlockedBy(string $userId): Collection
    {
        return Achievement::query()
            ->join('user_achievements', 'user_achievements.achievement_id', '=', 'achievements.id')
            ->where('user_achievements.user_id', $userId)
            ->where('user_achievements.is_completed', true)
            ->orderByDesc('user_achievements.updated_at')
            ->get(['achievements.*', 'user_achievements.updated_at as unlocked_at']);
    }
}
