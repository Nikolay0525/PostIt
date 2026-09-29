<?php

namespace App\Repositories\Eloquent;

use App\Models\Group;
use App\Models\UserGroupSubscription;
use App\Repositories\Contracts\GroupRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class EloquentGroupRepository implements GroupRepositoryInterface
{
    public function find(string $id): ?Group
    {
        return Group::find($id);
    }

    public function findForGroupPage(string $id): ?Group
    {
        return Group::withCount('members')->with('currentRuleVersion')->find($id);
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

    // `UserGroupSubscription` has a composite primary key, which Eloquent does not support
    // natively: insertOrIgnore()/a WHERE-scoped delete() are used instead of instance methods,
    // same reasoning as EloquentVoteRepository.
    public function subscribe(string $groupId, string $userId): void
    {
        UserGroupSubscription::query()->insertOrIgnore([
            'group_id' => $groupId,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }

    public function unsubscribe(string $groupId, string $userId): void
    {
        UserGroupSubscription::query()
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->delete();
    }
}
