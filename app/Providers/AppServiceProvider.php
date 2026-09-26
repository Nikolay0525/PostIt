<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
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

        $this->app->bind(
            \App\Repositories\Contracts\CommentRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentCommentRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\GroupRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentGroupRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);

        // A single resource is sent as a plain object, without the `data` wrapper.
        // Paginated collections still come as { data, links, meta }.
        JsonResource::withoutWrapping();
    }
}
