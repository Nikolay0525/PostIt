<?php

namespace App\Http\Resources;

use App\Models\ModerationVerdict;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * An entry of the moderation log as one card of its list: what was decided, under which rule, and a
 * short start of the content, from the verdict's snapshot. The whole of it — the conversation, the
 * marks, the reason, the appeal — is on the entry's own page (LogEntryResource).
 *
 * @mixin ModerationVerdict
 */
class LogListItemResource extends JsonResource
{
    private const EXCERPT_LENGTH = 200;

    public function toArray(Request $request): array
    {
        $rule = $this->rule_index === null ? null : ($this->ruleVersion?->rules[$this->rule_index] ?? null);
        $context = $this->context ?? [];
        $comment = $context['comment'] ?? null;
        $post = $context['post'] ?? null;

        return [
            'id' => $this->id,
            'type' => $this->type->key(),
            'rule' => $rule === null ? null : [
                'index' => $this->rule_index,
                'text' => $rule['text'],
                'kind' => $this->ruleVersion->kindOfRule($this->rule_index)->key(),
            ],
            // What was judged: a comment, or a post.
            'content' => $comment !== null ? 'comment' : 'post',
            'title' => $comment === null ? ($post['title'] ?? null) : null,
            'excerpt' => Str::limit($comment['text'] ?? $post['article_text'] ?? '', self::EXCERPT_LENGTH),
            'is_own' => (bool) $this->is_own,
        ];
    }
}
