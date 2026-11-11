<?php

namespace App\Models;

use App\Enums\ModerationCaseStatus;
use App\Enums\ReportTargetType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ModerationCase extends BaseEntity
{
    protected $table = 'moderation_cases';

    protected $fillable = [
        'group_id',
        'target_type',
        'target_id',
        'status',
        'open_key',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'target_type' => ReportTargetType::class,
            'status' => ModerationCaseStatus::class,
            'closed_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'moderation_case_id');
    }

    public function verdict(): HasOne
    {
        return $this->hasOne(ModerationVerdict::class, 'moderation_case_id');
    }
}
