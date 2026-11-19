<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Logs a failed authentication attempt. Only the attempted login identifier
 * (email) is recorded — never the submitted password. A burst of these from
 * one source is the signal of credential guessing (see docs/logging_backup_policy.md).
 */
class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        Log::channel('security')->warning('auth.failed', [
            'event' => 'login_failed',
            'email' => $event->credentials['email'] ?? null,  // the password is never logged
            'guard' => $event->guard,
            'ip' => Request::ip(),
            'user_agent' => (string) Request::userAgent(),
        ]);
    }
}
