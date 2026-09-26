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
    /**
     * Plain lookup by id, without the author/group/aggregate loading findWithStats() does.
     * Used where the post itself is needed (e.g. authorization checks), not its display data.
     */
    public function find(string $id): ?Post;

    public function findWithStats(string $id): ?Post;

    public function paginateForGroup(string $groupId, PostSort $sort, int $perPage): LengthAwarePaginator;

    /**
     * Posts from public groups created within the last $days days, best score first.
     */
    public function paginateTrending(int $days, int $perPage): LengthAwarePaginator;

    /**
     * Posts from every group the user is subscribed to, newest first.
     */
    public function paginateForSubscriber(string $userId, int $perPage): LengthAwarePaginator;
}
