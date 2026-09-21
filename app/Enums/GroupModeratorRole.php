<?php

namespace App\Enums;

enum GroupModeratorRole: int
{
    case Owner = 0;
    case Moderator = 1;
}
