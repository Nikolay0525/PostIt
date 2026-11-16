<?php

namespace App\Models;

use App\Enums\ModerationVerdictType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ModerationVerdict extends BaseEntity
{
    protected $table = 'moderation_verdicts';

    protected $fillable = [
        'moderation_case_id',
        'type',
        'decided_by',
        'rule_version_id',
        'rule_index',
        'marks',
        'reason',
        'context',
    ];

    // `decided_by` is for reliability only, so it never leaves the server in a serialized model.
    protected $hidden = ['decided_by'];

    protected function casts(): array
    {
        return [
            'type' => ModerationVerdictType::class,
            // [{start, end, quote}, …]
            'marks' => 'array',
            // The conversation as it was when it was decided: { post, ancestors, comment, replies }.
            'context' => 'array',
        ];
    }

    public function moderationCase(): BelongsTo
    {
        return $this->belongsTo(ModerationCase::class, 'moderation_case_id');
    }

    public function ruleVersion(): BelongsTo
    {
        return $this->belongsTo(GroupRuleVersion::class, 'rule_version_id');
    }

    // The strike a "Warning" verdict gave.
    public function strike(): HasOne
    {
        return $this->hasOne(Strike::class, 'moderation_verdict_id');
    }

    // The author's appeal against it, if one was made.
    public function appeal(): HasOne
    {
        return $this->hasOne(Appeal::class, 'moderation_verdict_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
