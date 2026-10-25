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
}
