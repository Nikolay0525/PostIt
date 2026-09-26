<?php

namespace App\Repositories\Contracts;

use App\Enums\VoteParentType;
use App\Models\Vote;

interface VoteRepositoryInterface
{
    public function find(string $parentId, string $userId, VoteParentType $type): ?Vote;

    public function create(string $parentId, string $userId, VoteParentType $type, bool $positive): Vote;

    public function updateDirection(Vote $vote, bool $positive): Vote;

    public function delete(Vote $vote): void;

    /**
     * @return array{upvotes: int, downvotes: int}
     */
    public function countVotes(string $parentId, VoteParentType $type): array;
}
