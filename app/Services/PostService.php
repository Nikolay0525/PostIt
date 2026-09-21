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
    public function getPost(string $id): Post
    {
        return $this->postRepository->findWithStats($id)
            ?? throw (new ModelNotFoundException)->setModel(Post::class, [$id]);
    }

    public function getGroupPosts(string $groupId, PostSort $sort = PostSort::Newest): LengthAwarePaginator
    {
        return $this->postRepository->paginateForGroup($groupId, $sort, self::PER_PAGE);
    }

    public function getTrendingPosts(): LengthAwarePaginator
    {
        return $this->postRepository->paginateTrending(self::TRENDING_DAYS, self::PER_PAGE);
    }
}
