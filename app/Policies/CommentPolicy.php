<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Policies\Concerns\ChecksGroupBans;

class CommentPolicy
{
    use ChecksGroupBans;

    /**
     * A user may comment on (or reply within) a post once verified and not banned from its
     * group. Unlike PostPolicy::create(), group membership is not checked here: if the post is
     * visible to the user at all, Community has already applied its private-group visibility
     * rule, so no separate membership check is needed to comment on it.
     */
    public function create(User $user, Post $post): bool
    {
        if (! $user->hasVerifiedEmail() || $post->is_deleted) {
            return false;
        }

        return ! $this->isBannedFromGroup($user, $post->group_id);
    }

    /**
     * A user may vote on any comment except their own, and only while it has not been deleted.
     */
    public function vote(User $user, Comment $comment): bool
    {
        return ! $comment->is_deleted && $user->id !== $comment->user_id;
    }
}
