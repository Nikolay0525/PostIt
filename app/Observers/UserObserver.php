<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserSettings;
use App\Models\UserCounter;

class UserObserver
{
    public function created(User $user): void
    {
        UserSettings::create([
            'user_id' => $user->id,
            'ui_language_id' => config('app.default_ui_language_id'),
            'speaking_language_id' => config('app.default_speaking_language_id'),
        ]);

        UserCounter::create(['user_id' => $user->id]);
    }
}