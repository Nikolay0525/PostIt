<?php

namespace App\Repositories\Eloquent;

use App\Enums\VoteParentType;
use App\Models\Vote;
use App\Repositories\Contracts\VoteRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class EloquentVoteRepository implements VoteRepositoryInterface
{
    public function find(string $parentId, string $userId, VoteParentType $type): ?Vote
    {
        return $this->scoped($parentId, $type)->where('user_id', $userId)->first();
    }

    public function create(string $parentId, string $userId, VoteParentType $type, bool $positive): Vote
    {
        return Vote::create([
            'parent_id' => $parentId,
            'user_id' => $userId,
            'parent_type' => $type,
            'positive' => $positive,
        ]);
    }

    // `Vote` has a composite primary key, which Eloquent does not support natively: calling
    // update()/delete() on an already-fetched instance would not target the right row. Both
    // methods below build their own query from the vote's key columns instead of relying on it.
    public function updateDirection(Vote $vote, bool $positive): Vote
    {
        $this->scoped($vote->parent_id, $vote->parent_type)
            ->where('user_id', $vote->user_id)
            ->update(['positive' => $positive]);

        $vote->positive = $positive;

        return $vote;
    }

    public function delete(Vote $vote): void
    {
        $this->scoped($vote->parent_id, $vote->parent_type)
            ->where('user_id', $vote->user_id)
            ->delete();
    }

    public function countVotes(string $parentId, VoteParentType $type): array
    {
        $base = $this->scoped($parentId, $type);

        return [
            'upvotes' => (clone $base)->where('positive', true)->count(),
            'downvotes' => (clone $base)->where('positive', false)->count(),
        ];
    }

    private function scoped(string $parentId, VoteParentType $type): Builder
    {
        return Vote::query()->where('parent_id', $parentId)->where('parent_type', $type);
    }
}
