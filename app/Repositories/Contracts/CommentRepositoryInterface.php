<?php

namespace App\Repositories\Contracts;

use App\Models\Comment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Comments are returned with their author and the aggregates upvotes_count and downvotes_count.
 * Deleted comments are kept, so a thread never loses the replies below them.
 *
 * $viewerId, where accepted, adds that user's own vote as `viewer_vote` (true/false/null) to
 * every comment in the thread, including replies; omitted or null (a guest) leaves it unset.
 */
interface CommentRepositoryInterface
{
    /**
     * Plain lookup by id, without the author/aggregate loading paginateThreadsForPost() does.
     * Used where the comment itself is needed (e.g. authorization checks), not its display data.
     */
    public function find(string $id): ?Comment;

    /**
     * Paginates the top-level comments of a post, oldest first. Every comment
     * in the page has all of its descendants loaded in the nested `replies` relation.
     */
    public function paginateThreadsForPost(string $postId, int $perPage, ?string $viewerId = null): LengthAwarePaginator;
}
