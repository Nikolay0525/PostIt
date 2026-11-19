<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Assigns a unique request id and shares it with the logger, so every log
 * record produced while handling one request carries the same request_id.
 * This lets related events across components be correlated (see
 * docs/logging_backup_policy.md). An incoming X-Request-Id is honoured so an
 * id can be propagated from an upstream proxy.
 */
class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) $request->headers->get('X-Request-Id', (string) Str::uuid());

        $request->attributes->set('request_id', $requestId);
        Log::shareContext(['request_id' => $requestId]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
