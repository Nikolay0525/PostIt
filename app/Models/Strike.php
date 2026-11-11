<?php

namespace App\Models;

use App\Enums\SanctionKind;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Strike extends BaseEntity
{
    protected $table = 'strikes';

    protected $fillable = [
        'user_id',
        'group_id',
        'kind',
        'moderation_verdict_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'kind' => SanctionKind::class,
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function verdict(): BelongsTo
    {
        return $this->belongsTo(ModerationVerdict::class, 'moderation_verdict_id');
    }
}
