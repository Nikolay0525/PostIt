<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpeakingLanguage extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $table = 'speaking_languages';

    protected $fillable = [
        'name',
    ];

    public function userSettings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserSettings::class, 'speaking_language_id');
    }
}