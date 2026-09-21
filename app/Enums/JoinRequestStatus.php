<?php

namespace App\Enums;

enum JoinRequestStatus: int
{
    case Waiting = 0;
    case Accepted = 1;
    case Rejected = 2;
}
