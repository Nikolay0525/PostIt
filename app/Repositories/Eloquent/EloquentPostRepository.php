<?php

namespace App\Repositories\Eloquent;

use App\Enums\PostSort;
use App\Enums\VoteParentType;
use App\Models\Post;
use App\Models\Vote;
use App\Repositories\Contracts\PostRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EloquentPostRepository implements PostRepositoryInterface
{
    public function find(string $id): ?Post
    {
        return Post::find($id);
    }

    public function create(string $groupId, string $userId, ?string $title, string $article, string $slug): Post
    {
        return Post::create([
            'group_id' => $groupId,
            'user_id' => $userId,
            'title' => $title,
            'article' => $article,
            'slug' => $slug,
            'is_deleted' => false,
        ]);
    }

    public function findWithStatsBySlug(string $groupId, string $slug, ?string $viewerId = null): ?Post
    {
        return $this->withStats($viewerId)->where('group_id', $groupId)->where('slug', $slug)->first();
    }

    public function paginateForGroup(string $groupId, PostSort $sort, int $perPage, ?string $viewerId = null): LengthAwarePaginator
    {
        $query = $this->withStats($viewerId)->where('group_id', $groupId);

        match ($sort) {
            PostSort::Newest => $query->latest(),
            PostSort::Top => $this->orderByScore($query),
            PostSort::Controversy => $this->orderByControversy($query),
        };

        return $query->paginate($perPage);
    }

    public function randomSlugForGroup(string $groupId): ?string
    {
        return Post::query()
            ->where('group_id', $groupId)
            ->where('is_deleted', false)
            ->inRandomOrder()
            ->value('slug');
    }

    public function paginateTrending(int $days, int $perPage, ?string $viewerId = null): LengthAwarePaginator
    {
        $query = $this->withStats($viewerId)
            ->where('created_at', '>=', now()->subDays($days))
            ->whereHas('group', fn (Builder $group) => $group->where('is_private', false));

        return $this->orderByScore($query)->paginate($perPage);
    }

    public function paginateRecommended(string $userId, int $days, int $perPage): LengthAwarePaginator
    {
        $query = $this->withStats($userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->whereHas('group', fn (Builder $group) => $group->where('is_private', false))
            ->where('user_id', '!=', $userId)
            ->whereNotIn('group_id', fn ($groups) => $groups
                ->select('group_id')
                ->from('user_group_subscriptions')
                ->where('user_id', $userId))
            // 1 when the post's group is in a language the user speaks, else 0: those come first.
            // A plain EXISTS reads the same on MySQL and sqlite (the test driver).
            ->orderByRaw(
                'exists (select 1 from `groups` inner join `user_speaking_languages`'
                .' on `user_speaking_languages`.`language_code` = `groups`.`language_code`'
                .' where `groups`.`id` = `posts`.`group_id` and `user_speaking_languages`.`user_id` = ?) desc',
                [$userId]
            );

        return $this->orderByScore($query)->paginate($perPage);
    }

    public function paginateForSubscriber(string $userId, int $perPage): LengthAwarePaginator
    {
        // The subscriber is also the viewer here: this feed always shows the current user's own votes.
        return $this->withStats($userId)
            ->whereIn('group_id', fn ($groups) => $groups
                ->select('group_id')
                ->from('user_group_subscriptions')
                ->where('user_id', $userId))
            ->latest()
            ->paginate($perPage);
    }

    public function paginateForAuthor(string $authorId, int $perPage, ?string $viewerId = null): LengthAwarePaginator
    {
        $query = $this->withStats($viewerId)->where('user_id', $authorId);

        return $this->visibleTo($query, $viewerId)->latest()->paginate($perPage);
    }

    public function paginateForFollower(string $followerId, int $perPage): LengthAwarePaginator
    {
        // The follower is also the viewer: their own votes are shown, and private groups they're in.
        $query = $this->withStats($followerId)
            ->whereIn('user_id', fn ($authors) => $authors
                ->select('user_author_id')
                ->from('user_user_subscriptions')
                ->where('user_follower_id', $followerId));

        return $this->visibleTo($query, $followerId)->latest()->paginate($perPage);
    }

    // Posts in public groups, plus private groups the viewer is a member of (none for a guest).
    private function visibleTo(Builder $query, ?string $viewerId): Builder
    {
        return $query->where(function (Builder $visible) use ($viewerId) {
            $visible->whereHas('group', fn (Builder $group) => $group->where('is_private', false));

            if ($viewerId !== null) {
                $visible->orWhereIn('group_id', fn ($groups) => $groups
                    ->select('group_id')
                    ->from('user_group_subscriptions')
                    ->where('user_id', $viewerId));
            }
        });
    }

    private function withStats(?string $viewerId = null): Builder
    {
        $query = Post::query()
            ->with(['author:id,username,avatar_url', 'group:id,name,slug,is_private'])
            ->withCount([
                'votes as upvotes_count' => fn (Builder $votes) => $votes->where('positive', true),
                'votes as downvotes_count' => fn (Builder $votes) => $votes->where('positive', false),
                'comments',
            ])
            ->where('is_deleted', false);

        if ($viewerId !== null) {
            $query->addSelect(['viewer_vote' => Vote::query()
                ->select('positive')
                ->whereColumn('parent_id', 'posts.id')
                ->where('user_id', $viewerId)
                ->where('parent_type', VoteParentType::Post)
                ->limit(1),
            ]);
        }

        return $query;
    }

    // Score = upvotes - downvotes; newest first among equal scores.
    private function orderByScore(Builder $query): Builder
    {
        return $query->orderByRaw('(upvotes_count - downvotes_count) desc')->latest();
    }

    // A portable proxy for ComputesControversy's real (upvotes+downvotes)^(min/max) formula:
    // exponentiation, and MySQL's GREATEST/LEAST vs. sqlite's multi-arg min/max, aren't available
    // under one spelling that works both in production (MySQL) and in the test suite (sqlite).
    // `sum * (sum - |diff|) / (sum + |diff|)` only needs +, -, *, / and abs/nullif, which both
    // drivers support, and is monotonic in the same two ingredients as the real formula (bigger
    // total, more balanced split ranks higher) — it only has to order posts the same way, not
    // reproduce the exact number PostResource displays.
    private function orderByControversy(Builder $query): Builder
    {
        return $query->orderByRaw(
            '(upvotes_count + downvotes_count) * ((upvotes_count + downvotes_count) - abs(upvotes_count - downvotes_count))'
            .' / nullif((upvotes_count + downvotes_count) + abs(upvotes_count - downvotes_count), 0) desc'
        )->latest();
    }
}
