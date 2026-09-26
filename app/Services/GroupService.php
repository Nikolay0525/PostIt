<?php

namespace App\Services;

use App\Models\Group;
use App\Repositories\Contracts\GroupRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GroupService
{
    public function __construct(
        protected GroupRepositoryInterface $groupRepository
    ) {}

    /**
     * @throws ModelNotFoundException when the group does not exist
     */
    public function getGroup(string $id): Group
    {
        return $this->groupRepository->findWithMembersCount($id)
            ?? throw (new ModelNotFoundException)->setModel(Group::class, [$id]);
    }

    /**
     * Guests ($userId = null) are never members.
     */
    public function isMember(string $groupId, ?string $userId): bool
    {
        return $userId !== null && $this->groupRepository->isMember($groupId, $userId);
    }

    public function hasSubscriptions(string $userId): bool
    {
        return $this->groupRepository->hasSubscriptions($userId);
    }

    /**
     * Posts of a private group are visible to its members only.
     */
    public function canViewPosts(Group $group, bool $isMember): bool
    {
        return ! $group->is_private || $isMember;
    }
}
