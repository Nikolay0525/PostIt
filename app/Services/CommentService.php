<?php

namespace App\Services;

use App\Models\Comment;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class CommentService
{
    private const PER_PAGE = 20;

    public function __construct(
        protected CommentRepositoryInterface $commentRepository
    ) {}

    public function getPostThreads(string $postId, ?string $viewerId = null): LengthAwarePaginator
    {
        return $this->commentRepository->paginateThreadsForPost($postId, self::PER_PAGE, $viewerId);
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

        return $this->commentRepository->create($postId, $userId, $text, $parentId);
    }
}
