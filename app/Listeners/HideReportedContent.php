<?php

namespace App\Listeners;

use App\Events\ModerationCaseOpened;
use App\Services\ModerationCaseService;

// A case on a post or comment hides it, until a moderator decides.
class HideReportedContent
{
    public function __construct(private ModerationCaseService $cases) {}

    public function handle(ModerationCaseOpened $event): void
    {
        $this->cases->hideContentOf($event->case);
    }
}
