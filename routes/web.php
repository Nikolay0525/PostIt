<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\VoteController;
use App\Services\GroupService;
use Illuminate\Support\Facades\Route;

// Page URLs use slugs: /groups/{groupSlug} and /groups/{groupSlug}/posts/{postSlug}. A group slug
// can never be a bare "-", so service pages live under /groups/-/… (and /groups/{slug}/-/…) and
// can't collide with a group, whatever it's named. Background JSON endpoints keep the UUID.
Route::pattern('groupSlug', GroupService::SLUG_ROUTE_PATTERN);
Route::pattern('postSlug', '[^/]+');

Route::get('/', [HomeController::class, 'index'])->name('home');

// Public pages: anyone can read, interactions are gated in the UI (and later on the server).
Route::get('/groups/{groupSlug}', [GroupController::class, 'show'])->name('groups.show');
Route::get('/groups/{groupSlug}/random-post', [PostController::class, 'random'])->name('groups.random_post');
Route::get('/groups/{groupSlug}/posts/{postSlug}', [PostController::class, 'show'])->name('posts.show');

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

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [SettingsController::class, 'update'])
        ->middleware('throttle:30,1')->name('settings.update');

    Route::post('/votes', [VoteController::class, 'store'])
        ->middleware('throttle:60,1')->name('votes.store');

    Route::post('/comments', [CommentController::class, 'store'])
        ->middleware('throttle:60,1')->name('comments.store');

    Route::get('/groups/{groupSlug}/-/create-post', [PostController::class, 'create'])->name('posts.create');
    Route::post('/posts', [PostController::class, 'store'])
        ->middleware('throttle:60,1')->name('posts.store');

    Route::get('/groups/-/create', [GroupController::class, 'create'])->name('groups.create');
    Route::post('/groups', [GroupController::class, 'store'])
        ->middleware('throttle:10,1')->name('groups.store');

    Route::post('/groups/{id}/subscribe', [MembershipController::class, 'subscribe'])
        ->whereUuid('id')->middleware('throttle:60,1')->name('groups.subscribe');
    Route::delete('/groups/{id}/subscribe', [MembershipController::class, 'unsubscribe'])
        ->whereUuid('id')->middleware('throttle:60,1')->name('groups.unsubscribe');
});
