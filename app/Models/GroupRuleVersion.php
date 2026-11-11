<?php

namespace App\Models;

use App\Models\Concerns\HasRuleKinds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One immutable snapshot of a group's rules. `rules` is an ordered list of
 * ['text' => string, 'example' => ?string, 'kind' => 'mute'|'ban']; a rule is referenced as (version id, index).
 * A rule without a kind counts as a mute (see HasRuleKinds).
 */
class GroupRuleVersion extends BaseEntity
{
    use HasFactory, HasRuleKinds;

    const UPDATED_AT = null;

    protected $table = 'group_rule_versions';

    protected $fillable = [
        'group_id',
        'rules',
    ];

    protected function casts(): array
    {
        return [
            'rules' => 'array',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}
