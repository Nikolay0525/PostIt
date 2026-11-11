<?php

namespace App\Http\Resources;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One report as the moderator sees it: what it says was broken, never who sent it. Expects the
 * report loaded with its ruleVersion, and without `reporter_id` (the repository leaves it out).
 *
 * @mixin Report
 */
class ModerationReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // A group rule is named by (version, index) and read from that version, not from the group's
        // current rules: the reporter saw the rules as they were then.
        $rule = $this->rule_index === null ? null : ($this->ruleVersion?->rules[$this->rule_index] ?? null);

        return [
            'id' => $this->id,
            'kind' => match (true) {
                $this->rule_index !== null => 'group_rule',
                $this->breaks_platform_rules => 'platform',
                default => 'other',
            },
            'rule' => $rule === null ? null : ['index' => $this->rule_index, 'text' => $rule['text']],
            'text' => $this->text,
            'created_at' => $this->created_at,
        ];
    }
}
