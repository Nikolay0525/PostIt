<?php

namespace App\Services;

use App\Enums\VoteParentType;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\VoteRepositoryInterface;
use Illuminate\Support\Facades\DB;

class VoteService
{
    public function __construct(
        protected VoteRepositoryInterface $voteRepository,
        protected PostRepositoryInterface $postRepository,
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Casts a new vote, changes an existing one, or removes it if the same direction is
     * submitted again (one vote per user per target). The author's karma in the target's group
     * follows the vote: +1 for an upvote, -1 for a downvote, posts and comments alike.
     *
     * @return array{upvotes: int, downvotes: int, viewer_vote: bool|null}
     */
    public function castVote(User $user, Post|Comment $target, VoteParentType $type, bool $positive): array
    {
        $viewerVote = DB::transaction(function () use ($user, $target, $type, $positive) {
            // Voting on a post means having seen it (post_views), even if the card was never
            // whole on screen. Taking the vote back doesn't make it unseen.
            if ($type === VoteParentType::Post) {
                $this->postRepository->recordView($target->id, $user->id);
            }

            $existing = $this->voteRepository->find($target->id, $user->id, $type);
            $groupId = $target instanceof Post ? $target->group_id : $target->post->group_id;

            if ($existing === null) {
                $this->voteRepository->create($target->id, $user->id, $type, $positive);
                $this->userRepository->addKarma($target->user_id, $groupId, $positive ? 1 : -1);

                return $positive;
            }

            if ($existing->positive === $positive) {
                $this->voteRepository->delete($existing);
                $this->userRepository->addKarma($target->user_id, $groupId, $positive ? -1 : 1);

                return null;
            }

            // Switching sides undoes the old vote and casts the new one.
            $this->voteRepository->updateDirection($existing, $positive);
            $this->userRepository->addKarma($target->user_id, $groupId, $positive ? 2 : -2);

            return $positive;
        });

        return [
            ...$this->voteRepository->countVotes($target->id, $type),
            'viewer_vote' => $viewerVote,
        ];
    }
}
