<?php

namespace App\Services;

use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Site search over posts, groups and people (FR-CON-011). The feed filters don't apply: search
 * finds everything the viewer may see, including posts they've already read.
 */
class SearchService
{
    // Shorter terms match nearly everything and say nothing.
    public const MIN_LENGTH = 2;

    public const MAX_LENGTH = 100;

    public function __construct(
        protected PostRepositoryInterface $postRepository,
        protected GroupRepositoryInterface $groupRepository,
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * The term as searched, or null when it's too short to search for.
     */
    public function normalize(?string $term): ?string
    {
        $term = mb_substr(trim((string) $term), 0, self::MAX_LENGTH);

        return mb_strlen($term) >= self::MIN_LENGTH ? $term : null;
    }

    // Posts of a private group only for its members (PostRepositoryInterface::search()).
    public function posts(string $term, int $perPage, ?string $viewerId): LengthAwarePaginator
    {
        return $this->postRepository->search($term, $perPage, $viewerId);
    }

    // Private groups included: they must stay findable so people can ask to join.
    public function groups(string $term, int $perPage): LengthAwarePaginator
    {
        return $this->groupRepository->search($term, $perPage);
    }

    public function people(string $term, int $perPage): LengthAwarePaginator
    {
        return $this->userRepository->search($term, $perPage);
    }
}
