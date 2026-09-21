<?php

namespace App\Enums;

enum ReportTargetType: int
{
    case Post = 1;
    case Comment = 2;
    case User = 3;
}
