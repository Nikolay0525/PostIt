<?php

namespace App\Services;

use App\Enums\GroupModeratorRole;
use App\Models\Group;
use App\Repositories\Contracts\GroupRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GroupService
{
    // Lowercase Latin letters and digits, words joined by single hyphens: "retro-gaming".
    public const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public const SLUG_MAX_LENGTH = 30;

    public function __construct(
        protected GroupRepositoryInterface $groupRepository
    ) {}

    /**
     * @throws ModelNotFoundException when the group does not exist
     */
    public function getGroup(string $id): Group
    {
        return $this->groupRepository->findForGroupPage($id)
            ?? throw (new ModelNotFoundException)->setModel(Group::class, [$id]);
    }

    /**
     * Creates the group together with its first rule version, and makes the creator its Owner
     * and a member — all or nothing, so a group can never exist without an Owner.
     *
     * @param  list<array{text: string, example?: ?string}>  $rules
     *
     * @throws InvalidArgumentException when the slug is malformed or already taken
     */
    public function createGroup(
        string $ownerId,
        string $name,
        string $slug,
        string $description,
        array $rules,
        string $languageCode,
        bool $isPrivate,
    ): Group {
        if (strlen($slug) > self::SLUG_MAX_LENGTH || ! preg_match(self::SLUG_PATTERN, $slug)) {
            throw new InvalidArgumentException('Group slug ['.$slug.'] must be up to '.self::SLUG_MAX_LENGTH.' lowercase Latin letters, digits and single hyphens.');
        }

        if ($this->groupRepository->slugExists($slug)) {
            throw new InvalidArgumentException("Group slug [{$slug}] is already taken.");
        }

        return DB::transaction(function () use ($ownerId, $name, $slug, $description, $rules, $languageCode, $isPrivate) {
            $group = $this->groupRepository->create($name, $slug, $description, $languageCode, $isPrivate);

            $this->groupRepository->addRuleVersion($group->id, array_map(fn (array $rule) => [
                'text' => $rule['text'],
                'example' => $rule['example'] ?? null,
            ], array_values($rules)));

            $this->groupRepository->addModerator($group->id, $ownerId, GroupModeratorRole::Owner);
            $this->groupRepository->subscribe($group->id, $ownerId);

            return $group;
        });
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
