<?php

namespace App\Services;

use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
}
