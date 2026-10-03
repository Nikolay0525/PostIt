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

    private function state(bool $isFollowing, string $authorId): JsonResponse
    {
        return response()->json([
            'is_following' => $isFollowing,
            'followers_count' => $this->followService->followersCount($authorId),
        ]);
    }
}
