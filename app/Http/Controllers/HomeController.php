<?php

namespace App\Http\Controllers;

use App\Enums\FeedPeriod;
use App\Enums\FeedSource;
use App\Enums\FeedType;
use App\Http\Resources\PostResource;
use App\Services\PostService;
use App\Support\FeedFilters;
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

        // The feed filters, shared by every tab. "Only new" and "only my languages" need an
        // account, so a guest's are always off whatever the URL says.
        $filters = new FeedFilters(
            onlyNew: $userId !== null && $request->boolean('new'),
            onlyMyLanguages: $userId !== null && $request->boolean('langs'),
            period: FeedPeriod::tryFrom((string) $request->query('period')) ?? FeedPeriod::All,
        );

        return Inertia::render('Home', [
            'feed' => $feed->value,
            'source' => $source->value,
            'filters' => [
                'new' => $filters->onlyNew,
                'langs' => $filters->onlyMyLanguages,
                'period' => $filters->period->value,
            ],
            // null: a guest on the Following tab, who has nothing to follow yet (the page asks
            // them to log in instead).
            'posts' => $feed === FeedType::Following && $userId === null
                ? null
                : Inertia::scroll(fn () => PostResource::collection(match (true) {
                    $feed === FeedType::Recommended => $this->postService->getRecommendedPosts($userId, $filters),
                    $source === FeedSource::People => $this->postService->getFollowedAuthorsPosts($userId, $filters),
                    default => $this->postService->getSubscribedPosts($userId, $filters),
                })),
        ]);
    }
}
