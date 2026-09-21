<?php

namespace App\Repositories\Eloquent;

use App\Models\Comment;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentCommentRepository implements CommentRepositoryInterface
{
    public function paginateThreadsForPost(string $postId, int $perPage): LengthAwarePaginator
    {
        $roots = $this->withStats()
            ->where('post_id', $postId)
            ->whereNull('parent_id')
            ->oldest()
            ->paginate($perPage);

        $this->attachReplies($roots->getCollection());

        return $roots;
    }

    // One query per nesting level, not one per comment.
    private function attachReplies(Collection $parents): void
    {
        if ($parents->isEmpty()) {
            return;
        }

        $children = $this->withStats()
            ->whereIn('parent_id', $parents->modelKeys())
            ->oldest()
            ->get();

        $childrenByParent = $children->groupBy('parent_id');

        foreach ($parents as $parent) {
            $parent->setRelation('replies', $childrenByParent->get($parent->id, $parent->newCollection()));
        }

        $this->attachReplies($children);
    }

    private function withStats(): Builder
    {
        return Comment::query()
            ->with('author:id,name')
            ->withCount([
                'votes as upvotes_count' => fn (Builder $votes) => $votes->where('positive', true),
                'votes as downvotes_count' => fn (Builder $votes) => $votes->where('positive', false),
            ]);
    }
}
