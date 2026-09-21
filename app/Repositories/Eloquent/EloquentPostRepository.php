<?php

namespace App\Repositories\Eloquent;

use App\Enums\PostSort;
use App\Models\Post;
use App\Repositories\Contracts\PostRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EloquentPostRepository implements PostRepositoryInterface
{
    public function findWithStats(string $id): ?Post
    {
        return $this->withStats()->find($id);
    }

    public function paginateForGroup(string $groupId, PostSort $sort, int $perPage): LengthAwarePaginator
    {
        $query = $this->withStats()->where('group_id', $groupId);

        match ($sort) {
            PostSort::Newest => $query->latest(),
            PostSort::Top => $this->orderByScore($query),
        };

        return $query->paginate($perPage);
    }

    public function paginateTrending(int $days, int $perPage): LengthAwarePaginator
    {
        $query = $this->withStats()
            ->where('created_at', '>=', now()->subDays($days))
            ->whereHas('group', fn (Builder $group) => $group->where('is_private', false));

        return $this->orderByScore($query)->paginate($perPage);
    }

    private function withStats(): Builder
    {
        return Post::query()
            ->with(['author:id,name', 'group:id,name,is_private'])
            ->withCount([
                'votes as upvotes_count' => fn (Builder $votes) => $votes->where('positive', true),
                'votes as downvotes_count' => fn (Builder $votes) => $votes->where('positive', false),
                'comments',
            ])
            ->where('is_deleted', false);
    }

    // Score = upvotes - downvotes; newest first among equal scores.
    private function orderByScore(Builder $query): Builder
    {
        return $query->orderByRaw('(upvotes_count - downvotes_count) desc')->latest();
    }
}
