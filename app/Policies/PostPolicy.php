<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * A user may vote on any post except their own, and only while it has not been deleted.
     */
    public function vote(User $user, Post $post): bool
    {
        return ! $post->is_deleted && $user->id !== $post->user_id;
    }
}
