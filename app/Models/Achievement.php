<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends BaseEntity
{
    public $timestamps = false;

    protected $table = 'achievements';

    protected $fillable = [
        'title',
        'description',
        'target_property',
        'target_value',
        'comparison_type',
        'icon_url',
    ];

    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class, 'achievement_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements', 'achievement_id', 'user_id')
            ->withPivot(['current_value', 'is_completed'])
            ->withTimestamps();
    }
}