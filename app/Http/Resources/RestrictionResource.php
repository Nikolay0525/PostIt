<?php

namespace App\Http\Resources;

use App\Models\Punishment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The mute or ban a member is under in a group, for that member's own page: what it is, and until
 * when (null is for good). Nobody else is told.
 *
 * @mixin Punishment
 */
class RestrictionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kind' => $this->kind->key(),
            'until' => $this->ends_at,
        ];
    }
}
