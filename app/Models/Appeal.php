<?php

namespace App\Models;

use App\Enums\AppealStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appeal extends BaseEntity
{
    protected $table = 'appeals';

    protected $fillable = [
        'moderation_verdict_id',
        'appellant_id',
        'statement',
        'status',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AppealStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    public function verdict(): BelongsTo
    {
        return $this->belongsTo(ModerationVerdict::class, 'moderation_verdict_id');
    }

    public function appellant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'appellant_id');
    }
}
