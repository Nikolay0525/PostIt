<?php

namespace App\Models;

use App\Models\Concerns\HasRuleKinds;

/**
 * One immutable snapshot of the platform's rules: an ordered list of
 * ['text' => string, 'example' => ?string, 'kind' => 'mute'|'ban'].
 */
class PlatformRuleVersion extends BaseEntity
{
    use HasRuleKinds;

    const UPDATED_AT = null;

    protected $table = 'platform_rule_versions';

    protected $fillable = ['rules'];

    /**
     * A rule's text in the language asked for, else in English. The platform's rules are written in
     * each language of the interface; a rule written as one plain text has only that.
     */
    public function textOfRule(int $index, ?string $locale = null): ?string
    {
        $text = $this->rules[$index]['text'] ?? null;

        if (! is_array($text)) {
            return $text;
        }

        return $text[$locale ?? app()->getLocale()] ?? $text['en'] ?? (reset($text) ?: null);
    }

    protected function casts(): array
    {
        return [
            'rules' => 'array',
        ];
    }
}
