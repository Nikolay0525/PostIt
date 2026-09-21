<?php

namespace App\Repositories\Contracts;

use App\Enums\PostSort;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Every returned post carries its author, group and the aggregates
 * upvotes_count, downvotes_count and comments_count. Deleted posts are never returned.
 */
interface PostRepositoryInterface
{
    public function findWithStats(string $id): ?Post;

    public function paginateForGroup(string $groupId, PostSort $sort, int $perPage): LengthAwarePaginator;

    /**
     * Posts from public groups created within the last $days days, best score first.
     */
    public function paginateTrending(int $days, int $perPage): LengthAwarePaginator;
}
