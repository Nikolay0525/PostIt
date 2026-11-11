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

    protected function casts(): array
    {
        return [
            'rules' => 'array',
        ];
    }
}
