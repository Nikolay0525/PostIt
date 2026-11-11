<?php

namespace App\Repositories\Eloquent\Concerns;

use App\Enums\SanctionKind;
use App\Models\Punishment;

/**
 * A ban closes a group's content to the one banned, wherever its posts would turn up: the feeds,
 * search, a profile. The list of such groups is one small query (a member is banned from a handful
 * of groups at most), asked once per request that lists posts, and only for a logged-in viewer.
 */
trait ExcludesBannedGroups
{
    /**
     * @return list<string>
     */
    private function bannedGroupIds(string $userId): array
    {
        return Punishment::running()
            ->where('user_id', $userId)
            ->where('kind', SanctionKind::Ban->value)
            ->whereNotNull('group_id')
            ->pluck('group_id')
            ->all();
    }
}
