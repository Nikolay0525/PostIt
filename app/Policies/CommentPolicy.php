<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Policies\Concerns\ChecksGroupBans;
use App\Policies\Concerns\ChecksGroupVisibility;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    use ChecksGroupBans;
    use ChecksGroupVisibility;

    /**
     * A user may comment on (or reply within) a post they can see, once verified and not banned
     * from its group. Seeing it is the only membership rule: in a public group anyone may comment,
     * in a private one only members (FR-COM-006). A post they can't see is "not found".
     */
    public function create(User $user, Post $post): Response|bool
    {
        if (! $this->canSeePostsOf($user, $post->group)) {
            return Response::denyAsNotFound();
        }

        if (! $user->hasVerifiedEmail() || $post->is_deleted) {
            return false;
        }

        return ! $this->isBannedFromGroup($user, $post->group_id);
    }

    /**
     * A user may vote on any comment they can see except their own, and only while it has not
     * been deleted.
     */
    public function vote(User $user, Comment $comment): Response|bool
    {
        if (! $this->canSeePostsOf($user, $comment->post->group)) {
            return Response::denyAsNotFound();
        }

        return ! $comment->is_deleted && $user->id !== $comment->user_id;
    }
}
