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
        'rule_version_id',
        'rule_index',
        'breaks_platform_rules',
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
            'breaks_platform_rules' => 'boolean',
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

    public function ruleVersion(): BelongsTo
    {
        return $this->belongsTo(GroupRuleVersion::class, 'rule_version_id');
    }
}