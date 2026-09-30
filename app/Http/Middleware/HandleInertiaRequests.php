<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'locale' => fn () => app()->getLocale(),
            'auth.user' => fn () => $request->user()
                ? $request->user()->only('id', 'name')
                : null,
            'status' => fn () => $request->session()->get('status'),
            // Read by app.js so a saved theme mode applies without a reload; guests get null
            // (the 'browser' mode). The first paint uses the same values from <html> attributes.
            'theme' => fn () => $request->user()
                ? [
                    'mode' => $request->user()->settings->theme_mode->value,
                    'dark' => $request->user()->settings->dark_theme,
                ]
                : null,
        ]);
    }
}
