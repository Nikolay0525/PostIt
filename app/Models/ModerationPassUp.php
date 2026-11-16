<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A case passed up to the platform's administrators, with the moderator's hint: the platform rule,
 * the marks and the reason. Not a verdict; the case's verdict will be an administrator's.
 */
class ModerationPassUp extends BaseEntity
{
    protected $table = 'moderation_pass_ups';

    protected $fillable = [
        'moderation_case_id',
        'passed_up_by',
        'platform_rule_version_id',
        'rule_index',
        'marks',
        'reason',
    ];

    // Who passed it up is never shown, like a verdict's moderator.
    protected $hidden = ['passed_up_by'];

    protected function casts(): array
    {
        return [
            // [{field, start, end, quote}, …]
            'marks' => 'array',
        ];
    }

    public function moderationCase(): BelongsTo
    {
        return $this->belongsTo(ModerationCase::class, 'moderation_case_id');
    }

    public function platformRuleVersion(): BelongsTo
    {
        return $this->belongsTo(PlatformRuleVersion::class, 'platform_rule_version_id');
    }
}
