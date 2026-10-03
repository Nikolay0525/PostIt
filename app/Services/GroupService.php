<?php

namespace App\Services;

use App\Enums\GroupModeratorRole;
use App\Models\Group;
use App\Repositories\Contracts\GroupRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GroupService
{
    // Lowercase Latin letters and digits, words joined by single hyphens: "retro-gaming".
    // Also the route constraint for {groupSlug}: since a slug can never be a bare "-", service
    // pages live under /groups/-/… and /groups/{slug}/-/… and can't collide with any group.
    public const SLUG_ROUTE_PATTERN = '[a-z0-9]+(?:-[a-z0-9]+)*';

    public const SLUG_PATTERN = '/^'.self::SLUG_ROUTE_PATTERN.'$/';

    public const SLUG_MAX_LENGTH = 30;

    public function __construct(
        protected GroupRepositoryInterface $groupRepository
    ) {}

    /**
     * @throws ModelNotFoundException when the group does not exist
     */
    public function getGroupBySlug(string $slug): Group
    {
        return $this->groupRepository->findForGroupPage($slug)
            ?? throw (new ModelNotFoundException)->setModel(Group::class, [$slug]);
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

    /**
     * The user's groups for their profile; a private group only when the viewer is in it too.
     *
     * @return Collection<int, Group>
     */
    public function getMembershipsVisibleTo(string $userId, ?string $viewerId): Collection
    {
        return $this->groupRepository->membershipsVisibleTo($userId, $viewerId);
    }

    /**
     * Posts of a private group are visible to its members only.
     */
    public function canViewPosts(Group $group, bool $isMember): bool
    {
        return ! $group->is_private || $isMember;
    }
}
