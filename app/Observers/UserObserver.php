<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserSettings;
use App\Models\UserCounter;

class UserObserver
{
    public function created(User $user): void
    {
        $defaultUiLanguageId = UiLanguage::where('code', config('app.default_ui_language_code', 'uk'))
            ->value('id');

        $defaultSpeakingLanguageId = SpeakingLanguage::where('name', config('app.default_speaking_language_name', 'Ukrainian'))
            ->value('id');

        UserSettings::create([
            'user_id' => $user->id,
            'ui_language_id' => $defaultUiLanguageId,
            'speaking_language_id' => $defaultSpeakingLanguageId,
        ]);

        UserCounter::create(['user_id' => $user->id]);
    }
}