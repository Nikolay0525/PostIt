<?php

namespace App\Http\Controllers;

use App\Enums\SearchType;
use App\Http\Resources\GroupListItemResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserProfileResource;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    private const PER_PAGE = 20;

    // On the All tab: this many groups and people above the posts, with a link to the full tab.
    private const PREVIEW_SIZE = 4;

    public function __construct(
        protected SearchService $searchService
    ) {}

    // Public like the feed: guests search too, and see what guests may see.
    public function index(Request $request): Response
    {
        $term = $this->searchService->normalize($request->query('q'));
        $type = SearchType::tryFrom((string) $request->query('type')) ?? SearchType::All;
        $viewerId = $request->user()?->id;

        // A tab's own list scrolls (<InfiniteScroll>); on All, groups and people are a short
        // preview (their `meta.total` says whether there are more). null = not on this tab.
        $list = fn (SearchType $own, callable $results) => match (true) {
            $term === null => null,
            $type === $own => Inertia::scroll(fn () => $results(self::PER_PAGE)),
            $type === SearchType::All && $own !== SearchType::Posts => $results(self::PREVIEW_SIZE),
            $type === SearchType::All => Inertia::scroll(fn () => $results(self::PER_PAGE)),
            default => null,
        };

        return Inertia::render('Search', [
            'q' => trim((string) $request->query('q')),
            'type' => $type->value,
            'min_length' => SearchService::MIN_LENGTH,
            'posts' => $list(SearchType::Posts, fn (int $perPage) => PostResource::collection(
                $this->searchService->posts($term, $perPage, $viewerId)
            )),
            'groups' => $list(SearchType::Groups, fn (int $perPage) => GroupListItemResource::collection(
                $this->searchService->groups($term, $perPage)
            )),
            'people' => $list(SearchType::People, fn (int $perPage) => UserProfileResource::collection(
                $this->searchService->people($term, $perPage)
            )),
        ]);
    }
}
