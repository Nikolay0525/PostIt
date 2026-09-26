<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * A user may vote on any comment except their own, and only while it has not been deleted.
     */
    public function vote(User $user, Comment $comment): bool
    {
        return ! $comment->is_deleted && $user->id !== $comment->user_id;
    }
}
