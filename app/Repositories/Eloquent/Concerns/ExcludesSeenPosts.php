<?php

namespace App\Repositories\Eloquent\Concerns;

use App\Enums\VoteParentType;
use Illuminate\Database\Eloquent\Builder;

/**
 * What "seen" means for a post, in one place: the feed's "Only new" filter and the side panel's
 * new-posts counters must agree, so a post read anywhere stops being new everywhere.
 */
trait ExcludesSeenPosts
{
    // Opened (post_views) or voted on = seen. $posts is a query on `posts`.
    private function whereNotSeenBy(Builder $posts, string $userId): Builder
    {
        return $posts
            ->whereNotIn('posts.id', fn ($views) => $views
                ->select('post_id')
                ->from('post_views')
                ->where('user_id', $userId))
            ->whereNotIn('posts.id', fn ($votes) => $votes
                ->select('parent_id')
                ->from('votes')
                ->where('user_id', $userId)
                ->where('parent_type', VoteParentType::Post));
    }
}
