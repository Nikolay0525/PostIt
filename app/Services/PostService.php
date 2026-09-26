<?php

namespace App\Services;

use App\Enums\PostSort;
use App\Models\Post;
use App\Repositories\Contracts\PostRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PostService
{
    private const PER_PAGE = 20;

    private const TRENDING_DAYS = 7;

    public function __construct(
        protected PostRepositoryInterface $postRepository
    ) {}

    /**
     * @throws ModelNotFoundException when the post does not exist or was deleted
     */
    public function getPost(string $id, ?string $viewerId = null): Post
    {
        return $this->postRepository->findWithStats($id, $viewerId)
            ?? throw (new ModelNotFoundException)->setModel(Post::class, [$id]);
    }

    public function getGroupPosts(string $groupId, PostSort $sort = PostSort::Newest, ?string $viewerId = null): LengthAwarePaginator
    {
        return $this->postRepository->paginateForGroup($groupId, $sort, self::PER_PAGE, $viewerId);
    }

    public function getSubscribedPosts(string $userId): LengthAwarePaginator
    {
        return $this->postRepository->paginateForSubscriber($userId, self::PER_PAGE);
    }

    public function getTrendingPosts(?string $viewerId = null): LengthAwarePaginator
    {
        return $this->postRepository->paginateTrending(self::TRENDING_DAYS, self::PER_PAGE, $viewerId);
    }
}
