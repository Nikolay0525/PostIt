<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How alive a group was over the counting window (a week) before `computed_at`: `voters_count`
 * different people voted in it, in `votes_count` votes. A snapshot refreshed on a schedule, not a
 * live count.
 */
class GroupActivity extends Model
{
    protected $table = 'group_activity';
    protected $primaryKey = 'group_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'group_id',
        'voters_count',
        'votes_count',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'voters_count' => 'integer',
            'votes_count' => 'integer',
            'computed_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}
