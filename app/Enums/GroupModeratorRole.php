<?php

namespace App\Enums;

enum GroupModeratorRole: int
{
    case Owner = 0;
    case Moderator = 1;

    // What the page calls it (lang groups.show.role.<key>).
    public function key(): string
    {
        return strtolower($this->name);
    }
}
