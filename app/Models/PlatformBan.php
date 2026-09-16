<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformBan extends BaseEntity
{
    protected $table = 'platform_bans';

    protected $fillable = [
        'banned_user_id',
        'admin_id',
        'reason',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function bannedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_user_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function isActive(): bool
    {
        return is_null($this->expires_at) || $this->expires_at->isFuture();
    }
}