<?php

namespace App\Repositories\Eloquent;

use App\Enums\GroupModeratorRole;
use App\Models\Group;
use App\Models\GroupModerator;
use App\Models\GroupRuleVersion;
use App\Models\UserGroupSubscription;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Eloquent\Concerns\ExcludesSeenPosts;
use App\Repositories\Eloquent\Concerns\SearchesText;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentGroupRepository implements GroupRepositoryInterface
{
    use ExcludesSeenPosts, SearchesText;

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
        return Group::withCount('members')->with(['currentRuleVersion', 'language:code,native_name'])->where('slug', $slug)->first();
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

    public function update(string $groupId, string $name, string $description, string $languageCode): void
    {
        Group::whereKey($groupId)->update([
            'name' => $name,
            'description' => $description,
            'language_code' => $languageCode,
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
            ->leftJoin('user_group_karma', fn ($karma) => $karma
                ->on('user_group_karma.group_id', '=', 'groups.id')
                ->where('user_group_karma.user_id', $userId))
            ->select(['groups.id', 'groups.slug', 'groups.name', 'groups.icon_url', 'groups.is_private'])
            ->selectRaw('COALESCE(user_group_karma.karma, 0) as karma')
            ->withCasts(['karma' => 'integer'])
            ->orderBy('groups.name')
            ->get();
    }

    public function subscribedBy(string $userId): Collection
    {
        return $this->subscriptionsOf($userId)
            ->withCasts(['attached_on_feed' => 'boolean'])
            ->orderBy('groups.name')
            ->get([
                'groups.id', 'groups.slug', 'groups.name', 'groups.icon_url', 'groups.is_private',
                'user_group_subscriptions.attached_on_feed',
            ]);
    }

    public function attachedOnFeed(string $userId): Collection
    {
        // select() before withCount(): with the join, get($columns) would be ignored in favour of `*`.
        return $this->subscriptionsOf($userId)
            ->where('user_group_subscriptions.attached_on_feed', true)
            ->select(['groups.id', 'groups.slug', 'groups.name', 'groups.icon_url', 'groups.is_private', 'groups.language_code'])
            // New = posted since the user joined, by someone else, and not seen yet (ExcludesSeenPosts).
            ->withCount(['posts as new_posts_count' => fn (Builder $posts) => $this->whereNotSeenBy($posts
                ->where('posts.is_deleted', false)
                ->where('posts.user_id', '!=', $userId)
                ->whereColumn('posts.created_at', '>=', 'user_group_subscriptions.created_at'), $userId)])
            ->orderByDesc('new_posts_count')
            ->orderBy('groups.name')
            ->get();
    }

    public function syncAttachedOnFeed(string $userId, array $groupIds): void
    {
        UserGroupSubscription::query()
            ->where('user_id', $userId)
            ->update(['attached_on_feed' => false]);

        UserGroupSubscription::query()
            ->where('user_id', $userId)
            ->whereIn('group_id', $groupIds)
            ->update(['attached_on_feed' => true]);
    }

    private function subscriptionsOf(string $userId): Builder
    {
        return Group::query()
            ->join('user_group_subscriptions', 'user_group_subscriptions.group_id', '=', 'groups.id')
            ->where('user_group_subscriptions.user_id', $userId);
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
