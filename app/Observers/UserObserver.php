<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserCounters;
use App\Models\UserSettings;

class UserObserver
{
    public function created(User $user): void
    {
        UserSettings::create([
            'user_id' => $user->id,
            'ui_language_code' => config('app.default_ui_language_code', 'uk'),
            'speaking_language_code' => config('app.default_speaking_language_code', 'uk'),
        ]);

        UserCounters::create(['user_id' => $user->id]);
    }
}
