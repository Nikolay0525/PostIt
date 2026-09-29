<?php

namespace App\Repositories\Eloquent;

use App\Enums\GroupModeratorRole;
use App\Models\Group;
use App\Models\GroupModerator;
use App\Models\GroupRuleVersion;
use App\Models\UserGroupSubscription;
use App\Repositories\Contracts\GroupRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class EloquentGroupRepository implements GroupRepositoryInterface
{
    public function find(string $id): ?Group
    {
        return Group::find($id);
    }

    public function findBySlug(string $slug): ?Group
    {
        return Group::where('slug', $slug)->first();
    }

    public function findForGroupPage(string $slug): ?Group
    {
        return Group::withCount('members')->with('currentRuleVersion')->where('slug', $slug)->first();
    }

    public function slugExists(string $slug): bool
    {
        return Group::where('slug', $slug)->exists();
    }

    public function create(string $name, string $slug, string $description, string $languageCode, bool $isPrivate): Group
    {
        return Group::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'language_code' => $languageCode,
            'is_private' => $isPrivate,
        ]);
    }

    public function addRuleVersion(string $groupId, array $rules): GroupRuleVersion
    {
        return GroupRuleVersion::create([
            'group_id' => $groupId,
            'rules' => $rules,
        ]);
    }

    // Composite primary key, same workaround as subscribe() below.
    public function addModerator(string $groupId, string $userId, GroupModeratorRole $role): void
    {
        GroupModerator::query()->insert([
            'group_id' => $groupId,
            'user_id' => $userId,
            'role' => $role->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
