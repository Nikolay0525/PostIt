<?php

namespace App\Http\Controllers;

use App\Enums\VoteParentType;
use App\Http\Requests\CastVoteRequest;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Services\VoteService;
use App\Support\Concerns\ComputesControversy;
use Illuminate\Http\JsonResponse;

/**
 * A plain JSON endpoint, deliberately outside the Inertia request/response cycle: voting must
 * not trigger a full page visit, which would reset any comment/post list the reader has already
 * scrolled past its first page via <InfiniteScroll> (Inertia::scroll only returns page one on an
 * ordinary visit).
 */
class VoteController extends Controller
{
    use ComputesControversy;

    public function __construct(
        protected VoteService $voteService,
        protected PostRepositoryInterface $postRepository,
        protected CommentRepositoryInterface $commentRepository
    ) {}

    public function store(CastVoteRequest $request): JsonResponse
    {
        $type = $request->validated('target_type') === 'post' ? VoteParentType::Post : VoteParentType::Comment;
        $targetId = $request->validated('target_id');

        $target = match ($type) {
            VoteParentType::Post => $this->postRepository->find($targetId),
            VoteParentType::Comment => $this->commentRepository->find($targetId),
        };

        abort_if($target === null, 404);

        $this->authorize('vote', $target);

        $result = $this->voteService->castVote($request->user(), $target, $type, $request->boolean('positive'));

        return response()->json([
            'upvotes' => $result['upvotes'],
            'downvotes' => $result['downvotes'],
            'controversy' => $this->controversyScore($result['upvotes'], $result['downvotes']),
            'viewer_vote' => $result['viewer_vote'],
        ]);
    }
}
