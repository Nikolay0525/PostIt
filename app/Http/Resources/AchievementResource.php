<?php

namespace App\Http\Resources;

use App\Models\Achievement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An unlocked achievement; expects `unlocked_at` (see AchievementRepositoryInterface::unlockedBy()).
 *
 * @mixin Achievement
 */
class AchievementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'icon_url' => $this->icon_url,
            'unlocked_at' => $this->unlocked_at,
        ];
    }
}
