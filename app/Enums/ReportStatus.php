<?php

namespace App\Enums;

enum ReportStatus: int
{
    case Pending = 0;
    case Resolved = 1;
    case Escalated = 2;
    case Rejected = 3;
}