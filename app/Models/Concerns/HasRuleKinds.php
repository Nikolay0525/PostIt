<?php

namespace App\Models\Concerns;

use App\Enums\SanctionKind;

/**
 * For a model whose `rules` is an ordered list of {text, example, kind}: a rule's kind is fixed when
 * the rule is written, and shown next to it, so nobody chooses it case by case. A rule written before
 * kinds existed has none and counts as the milder one, a mute.
 */
trait HasRuleKinds
{
    public function kindOfRule(int $index): ?SanctionKind
    {
        $rule = $this->rules[$index] ?? null;

        return $rule === null ? null : SanctionKind::fromKey($rule['kind'] ?? null);
    }
}
