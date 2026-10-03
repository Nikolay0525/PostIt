<?php

namespace App\Policies\Concerns;

use App\Models\Group;
use App\Models\User;

/**
 * FR-COM-006: a private group's posts — and so their comments and votes — exist only for its
 * members. The group itself stays visible to everyone (people must be able to find it and ask to
 * join). Same rule as GroupService::canViewPosts(), which the group page and the random post use.
 */
trait ChecksGroupVisibility
{
    // $user null is a guest: they see public groups only.
    private function canSeePostsOf(?User $user, Group $group): bool
    {
        if (! $group->is_private) {
            return true;
        }

        return $user !== null && $group->members()->whereKey($user->id)->exists();
    }
}
