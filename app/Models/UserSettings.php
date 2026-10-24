<?php

namespace App\Models;

use App\Enums\ThemeMode;
use App\Enums\Tour;
use App\Enums\TourStatus;
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
        'amount_of_attached_subscriptions' => 10,
    ];

    protected $fillable = [
        'user_id', 'ui_language_code', 'dark_theme', 'theme_mode',
        'show_swear_words', 'show_adult_content', 'enable_cookies', 'allow_messages',
        'amount_of_attached_subscriptions', 'notification_preferences', 'muted_until', 'muted_forever', 'tours',
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
            'amount_of_attached_subscriptions' => 'integer',
            'notification_preferences' => 'array',
            'muted_until' => 'datetime',
            'muted_forever' => 'boolean',
            'tours' => 'array',
        ];
    }

    // Null when the tour has not been answered yet; a tour added later is simply missing from
    // older users' `tours`, so "missing" is the one way to say "not answered".
    public function tourStatus(Tour $tour): ?TourStatus
    {
        return TourStatus::tryFrom($this->tours[$tour->value] ?? '');
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
