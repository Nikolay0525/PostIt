<?php

namespace App\Events;

use App\Models\ModerationCase;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Enough reports came in on a post or comment, and a moderation case was opened on it.
 * Says only what happened; whoever needs to react listens for it.
 */
class ModerationCaseOpened
{
    use Dispatchable;

    public function __construct(public ModerationCase $case) {}
}
