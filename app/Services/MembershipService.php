<?php

namespace App\Services;

use App\Repositories\Contracts\GroupRepositoryInterface;

class MembershipService
{
    public function __construct(
        protected GroupRepositoryInterface $groupRepository
    ) {}

    public function subscribe(string $groupId, string $userId): void
    {
        $this->groupRepository->subscribe($groupId, $userId);
    }

    public function unsubscribe(string $groupId, string $userId): void
    {
        $this->groupRepository->unsubscribe($groupId, $userId);
    }

    // No cap on how many groups are attached: the side panel shows all of them.
    public function setAttachedOnFeed(string $groupId, string $userId, bool $attached): void
    {
        $this->groupRepository->setAttachedOnFeed($groupId, $userId, $attached);
    }
}
