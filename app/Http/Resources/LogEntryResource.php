<?php

namespace App\Http\Resources;

use App\Models\ModerationVerdict;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One entry of a group's moderation log: what was decided, under which rule and what it gave, where the
 * content broke it, the moderator's reason, and the conversation as it was then (the verdict's
 * snapshot). It names no moderator and no author — but to its own author, their own name, in place of the
 * "author" label: nobody else gets it. Its own author also gets what they need to appeal it.
 *
 * Expects what ModerationCaseRepositoryInterface::logOf() returns: the verdict with its ruleVersion and
 * appeal, and `is_own`.
 *
 * @mixin ModerationVerdict
 */
class LogEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rule = $this->rule_index === null ? null : ($this->ruleVersion?->rules[$this->rule_index] ?? null);
        $own = (bool) $this->is_own;

        return [
            'id' => $this->id,
            'decided_at' => $this->created_at,
            // "remove" or "warning"; with the rule's kind, what a warning gave: a strike towards a mute or a ban.
            'type' => $this->type->key(),
            'rule' => $rule === null ? null : [
                'index' => $this->rule_index,
                'text' => $rule['text'],
                'kind' => $this->ruleVersion->kindOfRule($this->rule_index)->key(),
            ],
            'marks' => $this->marks ?? [],
            'reason' => $this->reason,
            // { post, ancestors, comment, replies }, as CaseContextResource gives it; null for a verdict from
            // before snapshots were kept.
            'context' => $this->context,
            'is_own' => $own,
            // The viewer's own username, as it is now (a name is never kept with content), for the places
            // labelled "author": only on their own entry, where it tells them nothing they do not know.
            'author_username' => $own ? $request->user()->username : null,
            // For its author alone: whether it can still be appealed, and the appeal if they made one.
            'author_view' => $own ? (new AuthorVerdictResource($this->resource))->toArray($request) : null,
        ];
    }
}
