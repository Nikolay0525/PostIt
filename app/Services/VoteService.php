<?php

namespace App\Services;

use App\Enums\VoteParentType;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\VoteRepositoryInterface;
use Illuminate\Support\Facades\DB;

class VoteService
{
    public function __construct(
        protected VoteRepositoryInterface $voteRepository
    ) {}

    /**
     * Casts a new vote, changes an existing one, or removes it if the same direction is
     * submitted again (one vote per user per target).
     *
     * @return array{upvotes: int, downvotes: int}
     */
    public function castVote(User $user, Post|Comment $target, VoteParentType $type, bool $positive): array
    {
        DB::transaction(function () use ($user, $target, $type, $positive) {
            $existing = $this->voteRepository->find($target->id, $user->id, $type);

            match (true) {
                $existing === null => $this->voteRepository->create($target->id, $user->id, $type, $positive),
                $existing->positive === $positive => $this->voteRepository->delete($existing),
                default => $this->voteRepository->updateDirection($existing, $positive),
            };
        });

        return $this->voteRepository->countVotes($target->id, $type);
    }
}
