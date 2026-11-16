<?php

namespace App\Events;

use App\Models\ModerationPassUp;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A moderator passed a case up to the platform's administrators. Says only what happened; whoever needs
 * to react (the administrators' queue, when there is one) listens for it.
 */
class ModerationCasePassedUp
{
    use Dispatchable;

    public function __construct(public ModerationPassUp $passUp) {}
}
