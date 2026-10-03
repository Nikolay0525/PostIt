<?php

namespace App\Models;

use App\Enums\ThemeMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's preferences. The languages a user speaks are a list, so they live in
 * `user_speaking_languages` (User::speakingLanguages()), not here.
 */
class UserSettings extends Model
{
    protected $table = 'user_settings';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    // Same as the column default, so a row created in this request has it before any reload.
    protected $attributes = [
        'theme_mode' => ThemeMode::Browser->value,
    ];

    protected $fillable = [
        'user_id', 'ui_language_code', 'dark_theme', 'theme_mode',
        'show_swear_words', 'show_adult_content', 'enable_cookies', 'allow_messages',
    ];

    protected function casts(): array
    {
        return [
            'dark_theme' => 'boolean',
            'theme_mode' => ThemeMode::class,
            'show_swear_words' => 'boolean',
            'show_adult_content' => 'boolean',
            'enable_cookies' => 'boolean',
            'allow_messages' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function uiLanguage(): BelongsTo
    {
        return $this->belongsTo(UiLanguage::class, 'ui_language_code');
    }
}
