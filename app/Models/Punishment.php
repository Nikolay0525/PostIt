<?php

namespace App\Models;

use App\Enums\SanctionKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Punishment extends BaseEntity
{
    protected $table = 'punishments';

    protected $fillable = [
        'user_id',
        'group_id',
        'kind',
        'step',
        'starts_at',
        'ends_at',
        'lifted_at',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'kind' => SanctionKind::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'lifted_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    // Still in force: not taken back, and either permanent or not over yet.
    public function isRunning(): bool
    {
        return $this->lifted_at === null && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    // In force now: not taken back, and either permanent or not over yet.
    public function scopeRunning(Builder $query): void
    {
        $query->whereNull('lifted_at')
            ->where(fn (Builder $running) => $running->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    // The verdicts it was made of.
    public function verdicts(): BelongsToMany
    {
        return $this->belongsToMany(ModerationVerdict::class, 'punishment_verdicts', 'punishment_id', 'moderation_verdict_id');
    }
}
