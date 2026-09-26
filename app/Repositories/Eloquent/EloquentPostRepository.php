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

    public function findWithStats(string $id, ?string $viewerId = null): ?Post
    {
        return $this->withStats($viewerId)->find($id);
    }

    public function paginateForGroup(string $groupId, PostSort $sort, int $perPage, ?string $viewerId = null): LengthAwarePaginator
    {
        $query = $this->withStats($viewerId)->where('group_id', $groupId);

        match ($sort) {
            PostSort::Newest => $query->latest(),
            PostSort::Top => $this->orderByScore($query),
        };

        return $query->paginate($perPage);
    }

    public function paginateTrending(int $days, int $perPage, ?string $viewerId = null): LengthAwarePaginator
    {
        $query = $this->withStats($viewerId)
            ->where('created_at', '>=', now()->subDays($days))
            ->whereHas('group', fn (Builder $group) => $group->where('is_private', false));

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

    private function withStats(?string $viewerId = null): Builder
    {
        $query = Post::query()
            ->with(['author:id,name', 'group:id,name,is_private'])
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
}
