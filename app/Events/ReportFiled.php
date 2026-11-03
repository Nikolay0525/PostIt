<?php

namespace App\Events;

use App\Models\Report;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Someone reported a post or comment.
 * Says only what happened; whoever needs to react listens for it.
 */
class ReportFiled
{
    use Dispatchable;

    public function __construct(public Report $report) {}
}
