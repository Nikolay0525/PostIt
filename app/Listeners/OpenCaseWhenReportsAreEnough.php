<?php

namespace App\Listeners;

use App\Events\ReportFiled;
use App\Services\ModerationCaseService;

// Each report may be the one that tips the count: the service checks, and opens the case if so.
class OpenCaseWhenReportsAreEnough
{
    public function __construct(private ModerationCaseService $cases) {}

    public function handle(ReportFiled $event): void
    {
        $this->cases->openIfReported($event->report);
    }
}
