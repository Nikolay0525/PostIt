<?php

namespace App\Repositories\Contracts;

use App\Models\Achievement;
use Illuminate\Database\Eloquent\Collection;

interface AchievementRepositoryInterface
{
    /**
     * Achievements the user has completed, most recently unlocked first. Each carries
     * `unlocked_at` (when its progress row was completed).
     *
     * @return Collection<int, Achievement>
     */
    public function unlockedBy(string $userId): Collection;
}
