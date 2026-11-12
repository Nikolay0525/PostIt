<?php

namespace App\Policies;

use App\Enums\GroupModeratorRole;
use App\Models\Group;
use App\Models\GroupModerator;
use App\Models\User;

/**
 * The group itself is never hidden — a private group must stay findable so people can ask to
 * join it. Only its posts are restricted, and that rule lives with the posts (PostPolicy::view(),
 * GroupService::canViewPosts()).
 */
class GroupPolicy
{
    /**
     * Any verified user may found a group (and becomes its Owner). There is no group yet to be
     * banned from; platform-wide bans aren't enforced by any policy yet.
     */
    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    /**
     * Only the group's Owner edits it. A moderator does not, and neither does a member.
     */
    public function update(User $user, Group $group): bool
    {
        return GroupModerator::query()
            ->where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->where('role', GroupModeratorRole::Owner->value)
            ->exists();
    }

    /**
     * The group's Owner and its moderators see its moderation cases. A member does not, and
     * neither does a moderator of another group.
     */
    public function moderate(User $user, Group $group): bool
    {
        return GroupModerator::query()
            ->where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * A public group can be subscribed to directly; a private group only grants membership
     * through an approved GroupJoinRequest (not built yet), so subscribing to one is never
     * allowed here. A ban does not stop it: it closes the group's content (PostPolicy::view()),
     * and a subscription opens nothing, so it is only the member's own list.
     */
    public function subscribe(User $user, Group $group): bool
    {
        return $user->hasVerifiedEmail() && ! $group->is_private;
    }
}
