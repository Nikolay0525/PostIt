<?php

namespace App\Repositories\Contracts;

use App\Models\Group;

interface GroupRepositoryInterface
{
    /**
     * The returned group carries the aggregate members_count.
     */
    public function findWithMembersCount(string $id): ?Group;
}
