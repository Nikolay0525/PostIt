<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Comments are returned with their author and the aggregates upvotes_count and downvotes_count.
 * Deleted comments are kept, so a thread never loses the replies below them.
 */
interface CommentRepositoryInterface
{
    /**
     * Paginates the top-level comments of a post, oldest first. Every comment
     * in the page has all of its descendants loaded in the nested `replies` relation.
     */
    public function paginateThreadsForPost(string $postId, int $perPage): LengthAwarePaginator;
}
