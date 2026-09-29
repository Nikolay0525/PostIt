<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SettingsService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * @throws ModelNotFoundException when the user does not exist
     */
    public function getSettings(string $userId): User
    {
        return $this->userRepository->findForSettings($userId)
            ?? throw (new ModelNotFoundException)->setModel(User::class, [$userId]);
    }

    /**
     * Saves the preferences and replaces the speaking languages together, so a failure never
     * leaves one half saved.
     *
     * @param  array{ui_language_code: string, dark_theme: bool, show_swear_words: bool, show_adult_content: bool, enable_cookies: bool, allow_messages: bool}  $settings
     * @param  list<string>  $speakingLanguageCodes
     *
     * @throws InvalidArgumentException when a minor tries to enable adult content
     */
    public function updateSettings(User $user, array $settings, array $speakingLanguageCodes): void
    {
        if ($settings['show_adult_content'] && ! $user->isAdult()) {
            throw new InvalidArgumentException('Adult content can only be enabled by users aged 18 or older.');
        }

        DB::transaction(function () use ($user, $settings, $speakingLanguageCodes) {
            $this->userRepository->updateSettings($user->id, $settings);
            $this->userRepository->syncSpeakingLanguages($user->id, $speakingLanguageCodes);
        });
    }
}
