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
}
