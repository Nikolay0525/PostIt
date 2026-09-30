<?php

namespace App\Http\Middleware;

use App\Repositories\Contracts\LanguageRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the interface language for the request: the user's setting, otherwise the best
 * active language the browser asks for, otherwise the configured default.
 */
class SetLocale
{
    public function __construct(
        protected LanguageRepositoryInterface $languageRepository
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->resolveLocale($request));

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $userLocale = $request->user()?->settings?->ui_language_code;

        if ($userLocale) {
            return $userLocale;
        }

        $active = $this->languageRepository->activeUiCodes();
        $default = config('app.default_ui_language_code', 'uk');

        // getPreferredLanguage() returns its first argument when nothing matches, so the
        // default goes first; it also treats "uk-UA" as a match for "uk".
        return $request->getPreferredLanguage(array_values(array_unique([$default, ...$active]))) ?? $default;
    }
}
