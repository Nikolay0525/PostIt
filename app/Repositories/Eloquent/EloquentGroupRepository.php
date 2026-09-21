<?php

namespace App\Repositories\Eloquent;

use App\Models\Group;
use App\Repositories\Contracts\GroupRepositoryInterface;

class EloquentGroupRepository implements GroupRepositoryInterface
{
    public function findWithMembersCount(string $id): ?Group
    {
        return Group::withCount('members')->find($id);
    }
}
