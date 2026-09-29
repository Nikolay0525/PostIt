<?php

namespace App\Http\Resources;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expects a group loaded with the members_count aggregate and currentRuleVersion
 * (see GroupRepositoryInterface::findForGroupPage()). A group with no rule version yet has no rules.
 *
 * @mixin Group
 */
class GroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'rules' => $this->currentRuleVersion?->rules ?? [],
            'icon_url' => $this->icon_url,
            'is_private' => $this->is_private,
            'members_count' => $this->members_count,
        ];
    }
}
