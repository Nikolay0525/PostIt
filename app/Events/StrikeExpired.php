<?php

namespace App\Events;

use App\Models\Strike;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A strike has run out and been removed. Says only what happened; whoever needs to tell the member
 * listens for it. The strike is already gone from the database: this is the record of it.
 */
class StrikeExpired
{
    use Dispatchable;

    public function __construct(public Strike $strike) {}
}
