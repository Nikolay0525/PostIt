<?php

namespace App\Http\Controllers;

use App\Enums\FeedSource;
use App\Enums\FeedType;
use App\Http\Resources\PostResource;
use App\Services\PostService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        protected PostService $postService
    ) {}

    public function index(Request $request): Response
    {
        $userId = $request->user()?->id;

        // Recommended is the default tab for everyone. An unknown value falls back to the default
        // rather than failing: these come from a URL anyone can edit.
        $feed = FeedType::tryFrom((string) $request->query('feed')) ?? FeedType::Recommended;
        $source = FeedSource::tryFrom((string) $request->query('source')) ?? FeedSource::Groups;

        return Inertia::render('Home', [
            'feed' => $feed->value,
            'source' => $source->value,
            // null: a guest on the Following tab, who has nothing to follow yet (the page asks
            // them to log in instead).
            'posts' => $feed === FeedType::Following && $userId === null
                ? null
                : Inertia::scroll(fn () => PostResource::collection(match (true) {
                    // Recommendations v1 is still "trending in public groups"; personalising it
                    // is the next step.
                    $feed === FeedType::Recommended => $this->postService->getTrendingPosts($userId),
                    $source === FeedSource::People => $this->postService->getFollowedAuthorsPosts($userId),
                    default => $this->postService->getSubscribedPosts($userId),
                })),
        ]);
    }
}
