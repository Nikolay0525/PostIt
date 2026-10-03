<?php

namespace App\Http\Resources;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A group in a list (search results): what a row needs, without GroupResource's rules, which
 * would cost a query per group. Expects the members_count aggregate.
 *
 * @mixin Group
 */
class GroupListItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'is_private' => $this->is_private,
            'members_count' => $this->members_count,
        ];
    }
}
