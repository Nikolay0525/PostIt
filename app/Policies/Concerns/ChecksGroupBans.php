<?php

namespace App\Policies\Concerns;

use App\Models\GroupBan;
use App\Models\User;

trait ChecksGroupBans
{
    private function isBannedFromGroup(User $user, string $groupId): bool
    {
        return GroupBan::query()
            ->where('group_id', $groupId)
            ->where('blamed_user_id', $user->id)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }
}
