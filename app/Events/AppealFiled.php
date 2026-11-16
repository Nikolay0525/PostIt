<?php

namespace App\Events;

use App\Models\Appeal;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An author appealed a verdict. Says only what happened; whoever needs to react (calling a jury)
 * listens for it.
 */
class AppealFiled
{
    use Dispatchable;

    public function __construct(public Appeal $appeal) {}
}
