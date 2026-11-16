<?php

namespace App\Repositories\Contracts;

use App\Models\PlatformRuleVersion;

interface PlatformRuleRepositoryInterface
{
    /**
     * The platform's rules in force now: the latest version, or null if there are none yet.
     */
    public function current(): ?PlatformRuleVersion;
}
