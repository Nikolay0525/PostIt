<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function isAdult(): bool
    {
        return $this->date_of_birth->diffInYears(now()) >= 18;
    }
}
