<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;

/**
 * A plain JSON endpoint, deliberately outside the Inertia request/response cycle: posting a
 * comment must not trigger a full page visit, which would reset any comment list the reader has
 * already scrolled past its first page via <InfiniteScroll> (Inertia::scroll only returns page
 * one on an ordinary visit) — same reasoning as VoteController.
 */
class CommentController extends Controller
{
    public function __construct(
        protected CommentService $commentService,
        protected CommentRepositoryInterface $commentRepository,
        protected PostRepositoryInterface $postRepository
    ) {}

    public function store(StoreCommentRequest $request): JsonResponse
    {
        $post = $this->postRepository->find($request->validated('post_id'));

        abort_if($post === null, 404);

        $this->authorize('create', [Comment::class, $post]);

        $comment = $this->commentService->createComment(
            $post->id,
            $request->user()->id,
            $request->validated('text'),
            $request->validated('parent_id')
        );

        // A brand new comment has no votes yet and no replies: fill in what CommentResource
        // expects instead of re-querying for aggregates that can only be zero right now.
        $comment->setRelation('author', $request->user());
        $comment->setRelation('replies', $comment->newCollection());
        $comment->upvotes_count = 0;
        $comment->downvotes_count = 0;
        $comment->viewer_vote = null;

        return response()->json(new CommentResource($comment));
    }

    // Answers with just the new text and time: the client merges them into the comment it already
    // shows, so the rest (votes, replies) is not re-queried.
    public function update(UpdateCommentRequest $request, string $id): JsonResponse
    {
        $comment = $this->commentRepository->find($id);

        abort_if($comment === null || $comment->is_deleted, 404);

        $this->authorize('update', $comment);

        $this->commentService->updateComment($comment, $request->validated('text'));
        $comment->refresh();

        return response()->json(['text' => $comment->text, 'edited_at' => $comment->edited_at]);
    }
}
