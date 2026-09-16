<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends BaseEntity
{
    protected $table = 'groups';

    protected $fillable = [
        'name',
        'description',
        'rules',
        'group_language_id',
        'icon_url',
        'is_private',
    ];

    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
        ];
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(SpeakingLanguage::class, 'group_language_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'group_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_group_subscriptions', 'group_id', 'user_id')
            ->withPivot('created_at');
    }

    public function moderators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_moderators', 'group_id', 'user_id')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    public function joinRequests(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_join_requests', 'group_id', 'user_id')
            ->withPivot('status')
            ->withTimestamps();
    }
}