<?php

namespace App\Http\Resources;

use App\Models\ModerationVerdict;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A verdict as the one it was about sees it: what was done, which rule was broken and what it leads to,
 * where in their text, and the moderator's reason. Nobody who decided is in it, and nothing here goes
 * to anyone but the author of the content.
 *
 * Expects the verdict loaded with its ruleVersion.
 *
 * @mixin ModerationVerdict
 */
class AuthorVerdictResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rule = $this->rule_index === null ? null : ($this->ruleVersion?->rules[$this->rule_index] ?? null);

        return [
            'type' => $this->type->key(),
            // The rule as it was when the case opened, and what breaking it leads to: a mute or a ban strike.
            'rule' => $rule === null ? null : [
                'index' => $this->rule_index,
                'text' => $rule['text'],
                'kind' => $this->ruleVersion->kindOfRule($this->rule_index)->key(),
            ],
            // [{start, end, quote}]: positions in the plain text of the content, as the moderator read it.
            'marks' => $this->marks ?? [],
            'reason' => $this->reason,
            'decided_at' => $this->created_at,
        ];
    }
}
