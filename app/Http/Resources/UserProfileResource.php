<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A user's public information only — never email, date of birth or settings. Expects the
 * followers_count aggregate (see UserRepositoryInterface::findForProfile()).
 *
 * @mixin User
 */
class UserProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'avatar_url' => ImageService::url($this->avatar_url),
            'status_emoji' => $this->status_emoji,
            'status_text' => $this->status_text,
            'bio' => $this->bio,
            'joined_at' => $this->created_at,
            'followers_count' => $this->followers_count,
        ];
    }
}
