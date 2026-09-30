<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Http\Requests\UpdateThemeRequest;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingsService $settingsService,
        protected LanguageRepositoryInterface $languageRepository
    ) {}

    public function edit(Request $request): Response
    {
        $user = $this->settingsService->getSettings($request->user()->id);

        return Inertia::render('Settings/Edit', [
            'settings' => [
                'ui_language_code' => $user->settings->ui_language_code,
                'speaking_languages' => $user->speakingLanguages->pluck('code'),
                'theme_mode' => $user->settings->theme_mode->value,
                'show_swear_words' => $user->settings->show_swear_words,
                'show_adult_content' => $user->settings->show_adult_content,
                'enable_cookies' => $user->settings->enable_cookies,
                'allow_messages' => $user->settings->allow_messages,
            ],
            'can_enable_adult_content' => $user->isAdult(),
            'ui_languages' => $this->languageRepository->uiLanguages(),
            'speaking_languages' => $this->languageRepository->speakingLanguages(),
            'max_speaking_languages' => UpdateSettingsRequest::MAX_SPEAKING_LANGUAGES,
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->settingsService->updateSettings(
            $request->user(),
            $request->settings(),
            $request->validated('speaking_languages'),
        );

        return redirect()->route('settings.edit')->with('status', 'settings-saved');
    }

    // Called with fetch by the navbar theme button in the 'manual' mode, so no page visit.
    public function updateTheme(UpdateThemeRequest $request): HttpResponse
    {
        $this->settingsService->updateManualTheme($request->user(), $request->boolean('dark'));

        return response()->noContent();
    }
}
