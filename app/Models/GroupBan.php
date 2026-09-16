<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupBan extends Model
{
    public $incrementing = false;

    protected $table = 'group_bans';
    protected $primaryKey = ['group_id', 'blamed_user_id'];

    protected $fillable = [
        'blamed_user_id',
        'moderator_id',
        'group_id',
        'reason',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function blamedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blamed_user_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function isActive(): bool
    {
        return is_null($this->expires_at) || $this->expires_at->isFuture();
    }
}