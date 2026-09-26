<?php

namespace App\Repositories\Eloquent;

use App\Enums\VoteParentType;
use App\Models\Comment;
use App\Models\Vote;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentCommentRepository implements CommentRepositoryInterface
{
    public function find(string $id): ?Comment
    {
        return Comment::find($id);
    }

    public function paginateThreadsForPost(string $postId, int $perPage, ?string $viewerId = null): LengthAwarePaginator
    {
        $roots = $this->withStats($viewerId)
            ->where('post_id', $postId)
            ->whereNull('parent_id')
            ->oldest()
            ->paginate($perPage);

        $this->attachReplies($roots->getCollection(), $viewerId);

        return $roots;
    }

    // One query per nesting level, not one per comment.
    private function attachReplies(Collection $parents, ?string $viewerId): void
    {
        if ($parents->isEmpty()) {
            return;
        }

        $children = $this->withStats($viewerId)
            ->whereIn('parent_id', $parents->modelKeys())
            ->oldest()
            ->get();

        $childrenByParent = $children->groupBy('parent_id');

        foreach ($parents as $parent) {
            $parent->setRelation('replies', $childrenByParent->get($parent->id, $parent->newCollection()));
        }

        $this->attachReplies($children, $viewerId);
    }

    private function withStats(?string $viewerId = null): Builder
    {
        $query = Comment::query()
            ->with('author:id,name')
            ->withCount([
                'votes as upvotes_count' => fn (Builder $votes) => $votes->where('positive', true),
                'votes as downvotes_count' => fn (Builder $votes) => $votes->where('positive', false),
            ]);

        if ($viewerId !== null) {
            $query->addSelect(['viewer_vote' => Vote::query()
                ->select('positive')
                ->whereColumn('parent_id', 'comments.id')
                ->where('user_id', $viewerId)
                ->where('parent_type', VoteParentType::Comment)
                ->limit(1),
            ]);
        }

        return $query;
    }
}
