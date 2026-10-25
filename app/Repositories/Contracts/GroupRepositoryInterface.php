<?php

namespace App\Repositories\Contracts;

use App\Enums\GroupModeratorRole;
use App\Models\Group;
use App\Models\GroupRuleVersion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface GroupRepositoryInterface
{
    /**
     * Plain lookup by id, without the display data findForGroupPage() loads.
     * Used where the group itself is needed (e.g. authorization checks), not its display data.
     */
    public function find(string $id): ?Group;

    /**
     * Plain lookup by the slug used in page URLs.
     */
    public function findBySlug(string $slug): ?Group;

    /**
     * By slug; the returned group carries the aggregate members_count and its currentRuleVersion.
     */
    public function findForGroupPage(string $slug): ?Group;

    /**
     * Groups whose name, slug or description contains the term — private ones too, so they can
     * be found and joined. A match in the name first (exact, "starts with", "contains"), the
     * biggest first within each. Each carries `members_count`.
     */
    public function search(string $term, int $perPage): LengthAwarePaginator;

    public function slugExists(string $slug): bool;

    public function create(string $name, string $slug, string $description, string $languageCode, bool $isPrivate): Group;

    /**
     * Stores a new, immutable snapshot of the group's rules; it becomes the current version.
     *
     * @param  list<array{text: string, example: ?string}>  $rules
     */
    public function addRuleVersion(string $groupId, array $rules): GroupRuleVersion;

    public function addModerator(string $groupId, string $userId, GroupModeratorRole $role): void;

    public function isMember(string $groupId, string $userId): bool;

    /**
     * The groups a user is a member of, by name, as shown on their profile: public groups, and
     * private ones only when the viewer is a member too (a guest sees public groups only).
     *
     * @return Collection<int, Group>
     */
    public function membershipsVisibleTo(string $userId, ?string $viewerId): Collection;

    /**
     * Every group the user is subscribed to, by name, private ones included — the user's own
     * list (the subscriptions menu), not what others see on their profile. Each carries
     * `attached_on_feed`: whether it is on the user's side panel.
     *
     * @return Collection<int, Group>
     */
    public function subscribedBy(string $userId): Collection;

    /**
     * Every group the user attached to their side panel: the most new posts first, then by name.
     * Each carries `new_posts_count` — posts since the user joined, by others, not opened or
     * voted on yet.
     *
     * @return Collection<int, Group>
     */
    public function attachedOnFeed(string $userId): Collection;

    /**
     * Shows or hides the group on the user's side panel. A no-op when the user is not subscribed.
     */
    public function setAttachedOnFeed(string $groupId, string $userId, bool $attached): void;

    /**
     * Exactly these of the user's groups are on their side panel; the rest are taken off. An id
     * of a group the user is not in is ignored. The caller wraps it in a transaction.
     *
     * @param  list<string>  $groupIds
     */
    public function syncAttachedOnFeed(string $userId, array $groupIds): void;

    /**
     * Creates the membership row. Idempotent — a no-op if already subscribed. Only meaningful
     * for a public group; a private group's membership is created by an approved
     * GroupJoinRequest instead (JoinRequestService, not built yet) — the Service/Policy layer is
     * responsible for not calling this for a private group.
     */
    public function subscribe(string $groupId, string $userId): void;

    /**
     * Removes the membership row, if any.
     */
    public function unsubscribe(string $groupId, string $userId): void;
}
