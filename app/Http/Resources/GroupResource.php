<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expects a group loaded with the members_count aggregate.
 *
 * @mixin \App\Models\Group
 */
class GroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'rules' => $this->rules,
            'icon_url' => $this->icon_url,
            'is_private' => $this->is_private,
            'members_count' => $this->members_count,
        ];
    }
}
