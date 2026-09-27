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
     * A user may publish a post once verified, allowed in the group (a member, or the group is
     * public), and not banned from it.
     */
    public function create(User $user, Group $group): bool
    {
        if (! $user->hasVerifiedEmail()) {
            return false;
        }

        $isAllowedInGroup = ! $group->is_private || $group->members()->where('user_id', $user->id)->exists();

        if (! $isAllowedInGroup) {
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
