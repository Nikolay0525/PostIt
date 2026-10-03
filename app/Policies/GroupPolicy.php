<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use App\Policies\Concerns\ChecksGroupBans;

/**
 * The group itself is never hidden — a private group must stay findable so people can ask to
 * join it. Only its posts are restricted, and that rule lives with the posts (PostPolicy::view(),
 * GroupService::canViewPosts()).
 */
class GroupPolicy
{
    use ChecksGroupBans;

    /**
     * Any verified user may found a group (and becomes its Owner). There is no group yet to be
     * banned from; platform-wide bans aren't enforced by any policy yet.
     */
    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    /**
     * A public group can be subscribed to directly; a private group only grants membership
     * through an approved GroupJoinRequest (not built yet), so subscribing to one is never
     * allowed here.
     */
    public function subscribe(User $user, Group $group): bool
    {
        if (! $user->hasVerifiedEmail() || $group->is_private) {
            return false;
        }

        return ! $this->isBannedFromGroup($user, $group->id);
    }
}
