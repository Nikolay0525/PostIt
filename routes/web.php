<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;

Route::inertia('/','Home')->name('home');

// Public pages: anyone can read, interactions are gated in the UI (and later on the server).
Route::get('/posts/{id}', [PostController::class, 'show'])
    ->whereUuid('id')->name('posts.show');
Route::get('/groups/{id}', fn (string $id) => Inertia::render('Groups/Show', ['id' => $id]))
    ->whereUuid('id')->name('groups.show');

Route::middleware(['guest'])->group(function () {
    Route::inertia('/login', 'Auth/Login')->name('login');
    Route::inertia('/register', 'Auth/Register')->name('register');
    Route::inertia('/forgot-password', 'Auth/ForgotPassword')->name('forgot_password');

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/email/verify', [AuthController::class, 'verificationNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1')->name('verification.send');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});