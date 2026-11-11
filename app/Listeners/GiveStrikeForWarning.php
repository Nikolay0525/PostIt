<?php

namespace App\Listeners;

use App\Events\ModerationVerdictGiven;
use App\Services\StrikeService;

// A "Warning" verdict gives its author a strike; the other verdicts give none.
class GiveStrikeForWarning
{
    public function __construct(private StrikeService $strikes) {}

    public function handle(ModerationVerdictGiven $event): void
    {
        $this->strikes->strikeFor($event->verdict);
    }
}
