<?php

namespace App\Repositories\Eloquent;

use App\Enums\GroupModeratorRole;
use App\Models\Group;
use App\Models\GroupModerator;
use App\Models\GroupRuleVersion;
use App\Models\UserGroupSubscription;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Eloquent\Concerns\SearchesText;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentGroupRepository implements GroupRepositoryInterface
{
    use SearchesText;

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

    // Private groups included on purpose: a private group must stay findable so people can ask to
    // join it — only its posts are closed.
    public function search(string $term, int $perPage): LengthAwarePaginator
    {
        $query = $this->whereContains(
            Group::withCount('members'),
            ['groups.name', 'groups.slug', 'groups.description'],
            $term
        );

        return $this->orderByMatch($query, 'groups.name', $term)
            ->orderByDesc('members_count')
            ->orderBy('name')
            ->paginate($perPage);
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

    public function membershipsVisibleTo(string $userId, ?string $viewerId): Collection
    {
        return Group::query()
            ->whereHas('members', fn (Builder $members) => $members->whereKey($userId))
            ->where(function (Builder $visible) use ($viewerId) {
                $visible->where('is_private', false);

                if ($viewerId !== null) {
                    $visible->orWhereHas('members', fn (Builder $members) => $members->whereKey($viewerId));
                }
            })
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'icon_url', 'is_private']);
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
