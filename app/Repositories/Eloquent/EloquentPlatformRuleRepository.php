<?php

namespace App\Repositories\Eloquent;

use App\Models\PlatformRuleVersion;
use App\Repositories\Contracts\PlatformRuleRepositoryInterface;

class EloquentPlatformRuleRepository implements PlatformRuleRepositoryInterface
{
    public function current(): ?PlatformRuleVersion
    {
        return PlatformRuleVersion::latest('created_at')->latest('id')->first();
    }
}
