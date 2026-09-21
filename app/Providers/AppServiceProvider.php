<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Observers\UserObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
        \App\Repositories\Contracts\UserRepositoryInterface::class,
        \App\Repositories\Eloquent\EloquentUserRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\PostRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentPostRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
    }
}
