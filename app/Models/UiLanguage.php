<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UiLanguage extends BaseEntity
{
    use HasFactory;

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

    public function userSettings(): HasMany
    {
        return $this->hasMany(UserSettings::class, 'ui_language_id');
    }
}