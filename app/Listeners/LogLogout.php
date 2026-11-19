<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Logs the end of an authenticated session to the security channel.
 */
class LogLogout
{
    public function handle(Logout $event): void
    {
        Log::channel('security')->info('auth.logout', [
            'event' => 'logout',
            'user_id' => $event->user?->getAuthIdentifier(),
            'guard' => $event->guard,
            'ip' => Request::ip(),
        ]);
    }
}
