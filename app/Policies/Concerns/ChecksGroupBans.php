<?php

namespace App\Policies\Concerns;

use App\Enums\SanctionKind;
use App\Models\GroupBan;
use App\Models\User;
use App\Repositories\Contracts\SanctionRepositoryInterface;

trait ChecksGroupBans
{
    // Banned: cannot take part in the group at all, and cannot join it.
    private function isBannedFromGroup(User $user, string $groupId): bool
    {
        $banned = GroupBan::query()
            ->where('group_id', $groupId)
            ->where('blamed_user_id', $user->id)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();

        return $banned || $this->runningPunishmentIn($user, $groupId)?->kind === SanctionKind::Ban;
    }

    // Muted or banned: can read, but contribute nothing — no post, no comment, no vote, no edit.
    private function cannotContributeIn(User $user, string $groupId): bool
    {
        return $this->runningPunishmentIn($user, $groupId) !== null || $this->isBannedFromGroup($user, $groupId);
    }

    private function runningPunishmentIn(User $user, string $groupId)
    {
        return app(SanctionRepositoryInterface::class)->runningIn($user->id, $groupId);
    }
}
