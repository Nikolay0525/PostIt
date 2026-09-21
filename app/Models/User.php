<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['name', 'email', 'password', 'avatar_url', 'date_of_birth', 'role'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed', 
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'role' => UserRole::class,
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user) {
            $user->id ??= (string) Str::uuid();
        });
    }

    public function settings(): HasOne
    {
        return $this->hasOne(UserSettings::class, 'user_id');
    }

    public function counters(): HasOne
    {
        return $this->hasOne(UserCounter::class, 'user_id');
    }

    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'blocked_users',
            'user_id',
            'blocked_user_id'
        )->withPivot('created_at');
    }

    public function blockedBy(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'blocked_users',
            'blocked_user_id',
            'user_id'
        )->withPivot('created_at');
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_user_subscriptions',
            'user_follower_id',
            'user_author_id'
        )->withPivot('created_at');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_user_subscriptions',
            'user_author_id',
            'user_follower_id'
        )->withPivot('created_at');
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(
            Achievement::class,
            'user_achievements',
            'user_id',
            'achievement_id'
        )->withPivot('current_value', 'is_completed')
         ->withTimestamps();
    }

    public function subscribedGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            Group::class,
            'user_group_subscriptions',
            'user_id',
            'group_id'
        )->withTimestamps();
    }

    public function moderatedGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            Group::class,
            'group_moderators',
            'user_id',
            'group_id'
        )->withPivot('role')
         ->withTimestamps();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'user_id');
    }


    public function platformBans(): HasMany
    {
        return $this->hasMany(PlatformBan::class, 'banned_user_id');
    }

    public function groupBans(): HasMany
    {
        return $this->hasMany(GroupBan::class, 'blamed_user_id');
    }

    public function submittedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function groupJoinRequests(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_join_requests', 'user_id', 'group_id')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function isAdult(): bool
    {
        return $this->date_of_birth->diffInYears(now()) >= 18;
    }
}
