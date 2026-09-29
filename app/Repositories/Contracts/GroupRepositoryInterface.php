<?php

namespace App\Repositories\Contracts;

use App\Models\Group;

interface GroupRepositoryInterface
{
    /**
     * Plain lookup by id, without the display data findForGroupPage() loads.
     * Used where the group itself is needed (e.g. authorization checks), not its display data.
     */
    public function find(string $id): ?Group;

    /**
     * The returned group carries the aggregate members_count and its currentRuleVersion.
     */
    public function findForGroupPage(string $id): ?Group;

    public function isMember(string $groupId, string $userId): bool;

    public function hasSubscriptions(string $userId): bool;

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
