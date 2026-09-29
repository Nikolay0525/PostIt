<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class AuthService
{
    private const MAX_DETECTED_LANGUAGES = 5;

    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected LanguageRepositoryInterface $languageRepository
    ) {}

    /**
     * @param  list<string>  $browserLanguages  the browser's preferred locales, best first
     *                                          (e.g. ['uk', 'en_US', 'en']); pre-selects the
     *                                          user's speaking languages, editable in settings
     */
    public function register(array $data, array $browserLanguages = []): User
    {
        $user = $this->userRepository->create($data);

        $this->userRepository->syncSpeakingLanguages($user->id, $this->detectSpeakingLanguages($browserLanguages));

        event(new Registered($user));

        Auth::login($user);

        return $user;
    }

    public function attemptLogin(array $credentials, bool $remember = false): bool
    {
        return Auth::attempt($credentials, $remember);
    }

    public function logout(): void
    {
        Auth::logout();
    }

    /**
     * Returns one of the Password::* status constants.
     */
    public function sendResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    /**
     * Returns one of the Password::* status constants.
     */
    public function resetPassword(array $data): string
    {
        return Password::reset(
            $data,
            function (User $user, string $password) {
                $this->userRepository->updatePassword($user, $password);

                event(new PasswordReset($user));
            }
        );
    }

    // "en_US" and "en" both mean English here: only the primary language subtag matters for
    // speaking languages. Falls back to the configured default when nothing the browser sent
    // is a known language.
    private function detectSpeakingLanguages(array $browserLanguages): array
    {
        $codes = array_values(array_unique(array_map(
            fn (string $locale) => strtolower(strtok($locale, '_-')),
            $browserLanguages
        )));

        $known = array_slice($this->languageRepository->existingSpeakingCodes($codes), 0, self::MAX_DETECTED_LANGUAGES);

        return $known ?: $this->languageRepository->existingSpeakingCodes([config('app.default_speaking_language_code', 'uk')]);
    }

    /**
     * Returns false when the user was already verified and nothing was sent.
     */
    public function resendVerification(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->sendEmailVerificationNotification();

        return true;
    }
}
