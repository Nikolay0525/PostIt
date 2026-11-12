<?php

namespace App\Events;

use App\Models\Punishment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A mute or a ban has run its course. Says only what happened; whoever needs to tell the member
 * listens for it.
 */
class PunishmentEnded
{
    use Dispatchable;

    public function __construct(public Punishment $punishment) {}
}
