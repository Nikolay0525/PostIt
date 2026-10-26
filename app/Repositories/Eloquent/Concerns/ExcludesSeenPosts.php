<?php

namespace App\Repositories\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * What "seen" means for a post, in one place: the feed's "Only new" filter and the side panel's
 * new-posts counters must agree, so a post read anywhere stops being new everywhere.
 */
trait ExcludesSeenPosts
{
    // Seen = a row in `post_views`, which every way of seeing a post writes: its card whole on
    // screen in a list, its page opened, a vote on it. $posts is a query on `posts`.
    private function whereNotSeenBy(Builder $posts, string $userId): Builder
    {
        return $posts->whereNotIn('posts.id', fn ($views) => $views
            ->select('post_id')
            ->from('post_views')
            ->where('user_id', $userId));
    }
}
