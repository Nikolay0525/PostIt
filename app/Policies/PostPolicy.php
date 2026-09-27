<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Policies\Concerns\ChecksGroupBans;

class PostPolicy
{
    use ChecksGroupBans;

    /**
     * A user may publish a post once verified, a member of the group (public or private — being
     * able to read a public group's posts does not by itself grant posting rights in it), and
     * not banned from it.
     */
    public function create(User $user, Group $group): bool
    {
        if (! $user->hasVerifiedEmail()) {
            return false;
        }

        if (! $group->members()->where('user_id', $user->id)->exists()) {
            return false;
        }

        return ! $this->isBannedFromGroup($user, $group->id);
    }

    /**
     * A user may vote on any post except their own, and only while it has not been deleted.
     */
    public function vote(User $user, Post $post): bool
    {
        return ! $post->is_deleted && $user->id !== $post->user_id;
    }
}
