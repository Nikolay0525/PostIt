<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Logs a successful authentication to the security channel. No password or
 * token is recorded — only who, from where and the result.
 */
class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        Log::channel('security')->info('auth.login', [
            'event' => 'login_success',
            'user_id' => $event->user->getAuthIdentifier(),
            'guard' => $event->guard,
            'ip' => Request::ip(),
            'user_agent' => (string) Request::userAgent(),
        ]);
    }
}
