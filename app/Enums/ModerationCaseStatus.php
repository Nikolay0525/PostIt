<?php

namespace App\Enums;

enum ModerationCaseStatus: int
{
    // In the group's queue, waiting for a moderator.
    case Open = 0;
    // Decided: it has its verdict.
    case Closed = 1;
    // A moderator found it breaks the platform's rules and passed it up: out of the group's queue,
    // waiting for an administrator, whose decision will be its verdict. The content stays hidden.
    case PassedUp = 2;
}
