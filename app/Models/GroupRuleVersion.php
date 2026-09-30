<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One immutable snapshot of a group's rules. `rules` is an ordered list of
 * ['text' => string, 'example' => ?string]; a rule is referenced as (version id, index).
 */
class GroupRuleVersion extends BaseEntity
{
    use HasFactory;

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
