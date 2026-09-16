<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UiLanguage extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $table = 'ui_languages';

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function userSettings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserSettings::class, 'ui_language_id');
    }
}