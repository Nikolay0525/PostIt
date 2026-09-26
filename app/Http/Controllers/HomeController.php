<?php

namespace App\Http\Controllers;

use App\Enums\FeedType;
use App\Http\Resources\PostResource;
use App\Services\GroupService;
use App\Services\PostService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        protected PostService $postService,
        protected GroupService $groupService
    ) {}

    public function index(Request $request): Response
    {
        $userId = $request->user()?->id;

        // Members see posts from their groups. Guests, and members who have not subscribed
        // to anything yet, see what is trending so the page is never empty.
        $feed = $userId !== null && $this->groupService->hasSubscriptions($userId)
            ? FeedType::Subscriptions
            : FeedType::Trending;

        return Inertia::render('Home', [
            'feed' => $feed->value,
            'posts' => Inertia::scroll(fn () => PostResource::collection(
                $feed === FeedType::Subscriptions
                    ? $this->postService->getSubscribedPosts($userId)
                    : $this->postService->getTrendingPosts()
            )),
        ]);
    }
}
