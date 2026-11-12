<?php

namespace App\Services;

use App\Enums\ReportTargetType;
use App\Events\CommentCreated;
use App\Models\Comment;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class CommentService
{
    private const PER_PAGE = 20;

    public function __construct(
        protected CommentRepositoryInterface $commentRepository,
        protected ModerationCaseRepositoryInterface $moderationCaseRepository
    ) {}

    public function getPostThreads(string $postId, ?string $viewerId = null): LengthAwarePaginator
    {
        return $this->commentRepository->paginateThreadsForPost($postId, self::PER_PAGE, $viewerId);
    }

    /**
     * Puts, on each comment of the viewer's own that a verdict took down, the case it was decided in,
     * so that they are shown the rule, the marks and the reason. Walks the replies too; one query.
     */
    public function attachVerdictsForAuthor(LengthAwarePaginator $threads, string $viewerId): void
    {
        $own = [];
        $walk = function ($comments) use (&$walk, &$own, $viewerId) {
            foreach ($comments as $comment) {
                if ($comment->user_id === $viewerId && $comment->isRemoved()) {
                    $own[$comment->id] = $comment;
                }

                if ($comment->relationLoaded('replies')) {
                    $walk($comment->replies);
                }
            }
        };
        $walk($threads->getCollection());

        if ($own === []) {
            return;
        }

        $cases = $this->moderationCaseRepository->findLatestForMany(ReportTargetType::Comment, array_keys($own));

        foreach ($own as $id => $comment) {
            $comment->setRelation('moderationCase', $cases->get($id));
        }
    }

    /**
     * Adds a top-level comment on a post, or a reply when $parentId is given.
     *
     * @throws ModelNotFoundException when $parentId does not exist
     * @throws InvalidArgumentException when $parentId belongs to a different post
     */
    public function createComment(string $postId, string $userId, string $text, ?string $parentId = null): Comment
    {
        if ($parentId !== null) {
            $parent = $this->commentRepository->find($parentId)
                ?? throw (new ModelNotFoundException)->setModel(Comment::class, [$parentId]);

            if ($parent->post_id !== $postId) {
                throw new InvalidArgumentException('The parent comment does not belong to this post.');
            }
        }

        $comment = $this->commentRepository->create($postId, $userId, $text, $parentId);

        CommentCreated::dispatch($comment);

        return $comment;
    }

    /**
     * Saving what is already there is not an edit: nothing is written and "(edited)" does not appear.
     */
    public function updateComment(Comment $comment, string $text): void
    {
        if ($comment->text === $text) {
            return;
        }

        $this->commentRepository->update($comment->id, $text);
    }

    /**
     * Following a comment brings a notification for each reply to it.
     */
    public function follow(string $commentId, string $userId): void
    {
        $this->commentRepository->follow($commentId, $userId);
    }

    public function unfollow(string $commentId, string $userId): void
    {
        $this->commentRepository->unfollow($commentId, $userId);
    }

    public function isFollowing(string $commentId, string $userId): bool
    {
        return $this->commentRepository->isFollowing($commentId, $userId);
    }
}
