<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Services\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A plain JSON endpoint, deliberately outside the Inertia request/response cycle: subscribing
 * must not trigger a full page visit, which would reset the group's post list past its first
 * page via <InfiniteScroll> — same reasoning as VoteController/CommentController.
 */
class MembershipController extends Controller
{
    public function __construct(
        protected MembershipService $membershipService,
        protected GroupRepositoryInterface $groupRepository
    ) {}

    public function subscribe(Request $request, string $groupId): JsonResponse
    {
        $group = $this->groupRepository->find($groupId);

        abort_if($group === null, 404);

        $this->authorize('subscribe', $group);

        $this->membershipService->subscribe($group->id, $request->user()->id);

        return response()->json(['is_member' => true]);
    }

    // Leaving a group is never restricted the way joining is: no ban check, no privacy check —
    // removing your own membership can't be used to bypass anything.
    public function unsubscribe(Request $request, string $groupId): JsonResponse
    {
        $group = $this->groupRepository->find($groupId);

        abort_if($group === null, 404);

        $this->membershipService->unsubscribe($group->id, $request->user()->id);

        return response()->json(['is_member' => false]);
    }
}
