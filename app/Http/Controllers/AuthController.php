<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function register(RegisterRequest $request)
    {
        $this->authService->register($request->validated());

        return Redirect::route('verification.notice');
    }

    public function login(LoginRequest $request)
    {
        if ($this->authService->attemptLogin($request->validated(), $request->boolean('remember'))) {
            $request->session()->regenerate();

            return Redirect::intended(route('home'));
        }

        return Redirect::back()->withErrors(['email' => 'Invalid credentials'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $this->authService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::route('home');
    }

    public function sendResetLink(ForgotPasswordRequest $request)
    {
        $status = $this->authService->sendResetLink($request->validated('email'));

        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors(['email' => __($status)]);
        }

        // Same response whether or not the email exists, to avoid account enumeration.
        return back()->with('status', __(Password::RESET_LINK_SENT));
    }

    public function showResetForm(Request $request, string $token)
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = $this->authService->resetPassword($request->validated());

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($status)]);
        }

        return Redirect::route('login')->with('status', __($status));
    }

    public function verificationNotice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return Redirect::route('home');
        }

        return Inertia::render('Auth/EmailConfirmation');
    }

    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill();

        return Redirect::route('home');
    }

    public function resendVerification(Request $request)
    {
        if (! $this->authService->resendVerification($request->user())) {
            return Redirect::route('home');
        }

        return back()->with('status', 'verification-link-sent');
    }
}
