<?php

namespace App\Repositories\Eloquent;

use App\Models\Group;
use App\Repositories\Contracts\GroupRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class EloquentGroupRepository implements GroupRepositoryInterface
{
    public function findWithMembersCount(string $id): ?Group
    {
        return Group::withCount('members')->find($id);
    }

    public function isMember(string $groupId, string $userId): bool
    {
        return Group::whereKey($groupId)
            ->whereHas('members', fn (Builder $members) => $members->whereKey($userId))
            ->exists();
    }

    public function hasSubscriptions(string $userId): bool
    {
        return Group::whereHas('members', fn (Builder $members) => $members->whereKey($userId))->exists();
    }
}
