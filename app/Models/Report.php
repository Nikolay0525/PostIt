<?php

namespace App\Models;

use App\Enums\ReportStatus;
use App\Enums\ReportTargetType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends BaseEntity
{
    protected $table = 'reports';

    protected $fillable = [
        'reporter_id',
        'target_type',
        'target_id',
        'group_id',
        'text',
        'status',
        'reviewed_by',
        'resolution_note',
        'escalated_at',
    ];

    protected function casts(): array
    {
        return [
            'target_type' => ReportTargetType::class,
            'status' => ReportStatus::class,
            'escalated_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}