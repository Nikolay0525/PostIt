<?php

namespace App\Repositories\Eloquent;

use App\Enums\PostSort;
use App\Enums\VoteParentType;
use App\Models\Post;
use App\Models\Vote;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Support\FeedFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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

    // `post_views`/`post_shares` are link tables with a composite key and no model: insertOrIgnore
    // makes a repeat a no-op without a lookup first, and can't race into a duplicate-key error.
    public function recordView(string $postId, string $userId): void
    {
        DB::table('post_views')->insertOrIgnore(['post_id' => $postId, 'user_id' => $userId, 'viewed_at' => now()]);
    }

    public function recordShare(string $postId, string $userId): void
    {
        DB::table('post_shares')->insertOrIgnore(['post_id' => $postId, 'user_id' => $userId, 'created_at' => now()]);
    }

    public function sharesCount(string $postId): int
    {
        return DB::table('post_shares')->where('post_id', $postId)->count();
    }

    // True when the post's group is in a language the user speaks. A plain EXISTS reads the same
    // on MySQL and sqlite (the test driver); bound to the user's id.
    private const IN_USER_LANGUAGE = 'exists (select 1 from `groups` inner join `user_speaking_languages`'
        .' on `user_speaking_languages`.`language_code` = `groups`.`language_code`'
        .' where `groups`.`id` = `posts`.`group_id` and `user_speaking_languages`.`user_id` = ?)';

    public function paginateTrending(array $freshnessDays, int $perPage, ?string $viewerId = null, ?int $maxAgeDays = null): LengthAwarePaginator
    {
        $query = $this->withStats($viewerId)
            ->whereHas('group', fn (Builder $group) => $group->where('is_private', false));

        $this->withinDays($query, $maxAgeDays);

        return $this->orderByScore($this->orderByFreshness($query, $freshnessDays))->paginate($perPage);
    }

    public function paginateRecommended(string $userId, array $freshnessDays, int $perPage, FeedFilters $filters = new FeedFilters): LengthAwarePaginator
    {
        $query = $this->withStats($userId)
            ->whereHas('group', fn (Builder $group) => $group->where('is_private', false))
            ->where('user_id', '!=', $userId)
            ->whereNotIn('group_id', fn ($groups) => $groups
                ->select('group_id')
                ->from('user_group_subscriptions')
                ->where('user_id', $userId));

        $this->applyFilters($query, $userId, $filters);

        // Without the strict language filter, the user's languages are still a priority here:
        // they come first, so the list isn't empty while there are few posts.
        if (! $filters->onlyMyLanguages) {
            $query->orderByRaw(self::IN_USER_LANGUAGE.' desc', [$userId]);
        }

        return $this->orderByScore($this->orderByFreshness($query, $freshnessDays))->paginate($perPage);
    }

    // The home feed filters, the same on every tab. They only narrow a list; each feed keeps its
    // own order.
    private function applyFilters(Builder $query, string $userId, FeedFilters $filters): void
    {
        $this->withinDays($query, $filters->period->days());

        if ($filters->onlyNew) {
            // Opened or voted on = already seen.
            $query
                ->whereNotIn('posts.id', fn ($views) => $views
                    ->select('post_id')
                    ->from('post_views')
                    ->where('user_id', $userId))
                ->whereNotIn('posts.id', fn ($votes) => $votes
                    ->select('parent_id')
                    ->from('votes')
                    ->where('user_id', $userId)
                    ->where('parent_type', VoteParentType::Post));
        }

        if ($filters->onlyMyLanguages) {
            $query->whereRaw(self::IN_USER_LANGUAGE, [$userId]);
        }
    }

    private function withinDays(Builder $query, ?int $days): void
    {
        if ($days !== null) {
            $query->where('posts.created_at', '>=', now()->subDays($days));
        }
    }

    // Freshness buckets instead of a hard cut-off: with [7, 30], this week's posts come first,
    // then this month's, then everything older — so the list only runs out when the site does,
    // while new posts still lead. Buckets rather than a smooth decay (score / age^1.5): the power
    // function isn't spelled the same on MySQL and sqlite, same reason as orderByControversy().
    private function orderByFreshness(Builder $query, array $freshnessDays): Builder
    {
        $cases = '';
        $bindings = [];

        foreach (array_values($freshnessDays) as $bucket => $days) {
            $cases .= ' when `posts`.`created_at` >= ? then '.$bucket;
            $bindings[] = now()->subDays($days);
        }

        return $cases === ''
            ? $query
            : $query->orderByRaw('case'.$cases.' else '.count($bindings).' end', $bindings);
    }

    public function paginateForSubscriber(string $userId, int $perPage, FeedFilters $filters = new FeedFilters): LengthAwarePaginator
    {
        // The subscriber is also the viewer here: this feed always shows the current user's own votes.
        $query = $this->withStats($userId)
            ->whereIn('group_id', fn ($groups) => $groups
                ->select('group_id')
                ->from('user_group_subscriptions')
                ->where('user_id', $userId));

        $this->applyFilters($query, $userId, $filters);

        return $query->latest()->paginate($perPage);
    }

    public function paginateForAuthor(string $authorId, int $perPage, ?string $viewerId = null): LengthAwarePaginator
    {
        $query = $this->withStats($viewerId)->where('user_id', $authorId);

        return $this->visibleTo($query, $viewerId)->latest()->paginate($perPage);
    }

    public function paginateForFollower(string $followerId, int $perPage, FeedFilters $filters = new FeedFilters): LengthAwarePaginator
    {
        // The follower is also the viewer: their own votes are shown, and private groups they're in.
        $query = $this->withStats($followerId)
            ->whereIn('user_id', fn ($authors) => $authors
                ->select('user_author_id')
                ->from('user_user_subscriptions')
                ->where('user_follower_id', $followerId));

        $this->applyFilters($query, $followerId, $filters);

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
                'sharedBy as shares_count',
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
