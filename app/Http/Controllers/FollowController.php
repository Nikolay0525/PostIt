<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\FollowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A plain JSON endpoint, deliberately outside the Inertia request/response cycle: following must
 * not trigger a full page visit, which would reset the profile's post list past its first page
 * via <InfiniteScroll> — same reasoning as MembershipController.
 */
class FollowController extends Controller
{
    public function __construct(
        protected FollowService $followService,
        protected UserRepositoryInterface $userRepository
    ) {}

    public function store(Request $request, string $userId): JsonResponse
    {
        $author = $this->userRepository->find($userId);

        abort_if($author === null, 404);

        $this->authorize('follow', $author);

        $this->followService->follow($request->user()->id, $author->id);

        return $this->state(true, $author->id);
    }

    // Like leaving a group, unfollowing is never restricted.
    public function destroy(Request $request, string $userId): JsonResponse
    {
        $author = $this->userRepository->find($userId);

        abort_if($author === null, 404);

        $this->followService->unfollow($request->user()->id, $author->id);

        return $this->state(false, $author->id);
    }

    public function attach(Request $request, string $userId): JsonResponse
    {
        return $this->setAttachedOnFeed($request, $userId, true);
    }

    public function detach(Request $request, string $userId): JsonResponse
    {
        return $this->setAttachedOnFeed($request, $userId, false);
    }

    // Only someone the user follows can be on their side panel; 404 otherwise.
    private function setAttachedOnFeed(Request $request, string $authorId, bool $attached): JsonResponse
    {
        $followerId = $request->user()->id;

        abort_unless($this->followService->isFollowing($followerId, $authorId), 404);

        $this->followService->setAttachedOnFeed($followerId, $authorId, $attached);

        return response()->json(['attached_on_feed' => $attached]);
    }

    private function state(bool $isFollowing, string $authorId): JsonResponse
    {
        return response()->json([
            'is_following' => $isFollowing,
            'followers_count' => $this->followService->followersCount($authorId),
        ]);
    }
}
