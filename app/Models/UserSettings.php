<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSettings extends Model
{
    protected $table = 'user_settings';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'ui_language_id', 'speaking_language_id', 'dark_theme',
        'show_swear_words', 'show_adult_content', 'enable_cookies', 'allow_messages',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function uiLanguage(): BelongsTo
    {
        return $this->belongsTo(UILanguage::class, 'ui_language_id');
    }

    public function speakingLanguage(): BelongsTo
    {
        return $this->belongsTo(SpeakingLanguage::class, 'speaking_language_id');
    }
}