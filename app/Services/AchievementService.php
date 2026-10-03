<?php

namespace App\Services;

use App\Models\Achievement;
use App\Repositories\Contracts\AchievementRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read side only for now. Nothing awards achievements yet: progress tracking against
 * `user_counters` is still planned (Engagement module).
 */
class AchievementService
{
    public function __construct(
        protected AchievementRepositoryInterface $achievementRepository
    ) {}

    /**
     * @return Collection<int, Achievement>
     */
    public function getUnlocked(string $userId): Collection
    {
        return $this->achievementRepository->unlockedBy($userId);
    }
}
