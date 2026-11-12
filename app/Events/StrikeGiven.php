<?php

namespace App\Events;

use App\Models\Strike;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A "Warning" verdict gave its author a strike. Says only what happened: how many strikes of its kind
 * the member now has, and the limit at which they are punished; whoever needs to tell them listens.
 */
class StrikeGiven
{
    use Dispatchable;

    public function __construct(public Strike $strike, public int $count, public int $limit) {}
}
