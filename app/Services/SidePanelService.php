<?php

namespace App\Services;

use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;

/**
 * The side panel (home and group pages): the groups and people the user attached, each with its
 * new-posts count. Built the same for a page and for the answer to a "seen" batch, so the panel
 * can take either.
 */
class SidePanelService
{
    public function __construct(
        protected GroupRepositoryInterface $groupRepository,
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * @return array{groups: mixed, people: mixed, version: int}
     */
    public function forUser(string $userId): array
    {
        return [
            'groups' => $this->groupRepository->attachedOnFeed($userId),
            'people' => $this->userRepository->followsAttachedOnFeed($userId),
            // When this was built (ms). The browser keeps the newest it has seen: a page restored
            // from history (Back) or a partial reload carries an older copy, which must not win.
            'version' => (int) floor(microtime(true) * 1000),
        ];
    }
}
