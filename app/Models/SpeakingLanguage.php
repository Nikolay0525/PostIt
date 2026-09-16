<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpeakingLanguage extends BaseEntity
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'speaking_languages';

    protected $fillable = [
        'name',
    ];

    public function userSettings(): HasMany
    {
        return $this->hasMany(UserSettings::class, 'speaking_language_id');
    }
}