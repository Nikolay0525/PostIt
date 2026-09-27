<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use App\Policies\Concerns\ChecksGroupBans;

class GroupPolicy
{
    use ChecksGroupBans;

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
