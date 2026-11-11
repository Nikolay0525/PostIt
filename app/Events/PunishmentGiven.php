<?php

namespace App\Events;

use App\Models\Punishment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A member's strikes reached the limit, and a mute or a ban began. Says only what happened; whoever
 * needs to react (telling the member, keeping them from posting) listens for it.
 */
class PunishmentGiven
{
    use Dispatchable;

    public function __construct(public Punishment $punishment) {}
}
