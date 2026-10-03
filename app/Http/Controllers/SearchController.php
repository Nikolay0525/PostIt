<?php

namespace App\Http\Controllers;

use App\Enums\SearchType;
use App\Http\Resources\GroupListItemResource;
use App\Http\Resources\PostListItemResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserProfileResource;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    private const PER_PAGE = 20;

    // On the All tab: this many groups and people above the posts, with a link to the full tab.
    private const PREVIEW_SIZE = 4;

    // Under the navbar field while typing: this many of each kind.
    private const SUGGESTIONS = 3;

    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * Suggestions under the navbar field while typing: the best few of each kind, as JSON (the
     * field sits on every page, so this can't be a page visit). Same matching and visibility as
     * the results page; a too-short term gets empty lists.
     */
    public function suggest(Request $request): JsonResponse
    {
        $term = $this->searchService->normalize($request->query('q'));

        if ($term === null) {
            return response()->json(['groups' => [], 'people' => [], 'posts' => []]);
        }

        $viewerId = $request->user()?->id;

        return response()->json([
            'groups' => GroupListItemResource::collection($this->searchService->groups($term, self::SUGGESTIONS)->items()),
            'people' => UserProfileResource::collection($this->searchService->people($term, self::SUGGESTIONS)->items()),
            'posts' => PostListItemResource::collection($this->searchService->posts($term, self::SUGGESTIONS, $viewerId)->items()),
        ]);
    }

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
