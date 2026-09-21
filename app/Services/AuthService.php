<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    public function register(array $data): User
    {
        $user = $this->userRepository->create($data);

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
