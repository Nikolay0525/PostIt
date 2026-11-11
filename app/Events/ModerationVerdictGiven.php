<?php

namespace App\Events;

use App\Models\ModerationVerdict;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A moderator decided a case. Says only what happened; whoever needs to react (telling the author,
 * the moderation log, a warning) listens for it.
 */
class ModerationVerdictGiven
{
    use Dispatchable;

    public function __construct(public ModerationVerdict $verdict) {}
}
