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

    public function isMember(string $groupId, string $userId): bool
    {
        return $this->groupRepository->isMember($groupId, $userId);
    }

    // No cap on how many groups are attached: the side panel shows the first
    // `amount_of_attached_subscriptions` of them and links to the rest.
    public function setAttachedOnFeed(string $groupId, string $userId, bool $attached): void
    {
        $this->groupRepository->setAttachedOnFeed($groupId, $userId, $attached);
    }
}
