<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use InvalidArgumentException;

/**
 * Following an author (FR-ACC-010): their posts will reach the follower's home feed.
 */
class FollowService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * @throws InvalidArgumentException when a user tries to follow themselves
     */
    public function follow(string $followerId, string $authorId): void
    {
        if ($followerId === $authorId) {
            throw new InvalidArgumentException('A user cannot follow themselves.');
        }

        $this->userRepository->follow($followerId, $authorId);
    }

    public function unfollow(string $followerId, string $authorId): void
    {
        $this->userRepository->unfollow($followerId, $authorId);
    }

    // A guest ($followerId null) follows no one.
    public function isFollowing(?string $followerId, string $authorId): bool
    {
        return $followerId !== null && $this->userRepository->isFollowing($followerId, $authorId);
    }

    public function followersCount(string $authorId): int
    {
        return $this->userRepository->followersCount($authorId);
    }
}
