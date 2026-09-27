<?php

namespace App\Repositories\Contracts;

use App\Models\Group;

interface GroupRepositoryInterface
{
    /**
     * Plain lookup by id, without the members_count aggregate findWithMembersCount() loads.
     * Used where the group itself is needed (e.g. authorization checks), not its display data.
     */
    public function find(string $id): ?Group;

    /**
     * The returned group carries the aggregate members_count.
     */
    public function findWithMembersCount(string $id): ?Group;

    public function isMember(string $groupId, string $userId): bool;

    public function hasSubscriptions(string $userId): bool;
}
