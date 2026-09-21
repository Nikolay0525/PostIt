<?php

namespace App\Enums;

enum ImageOwnerType: int
{
    case User = 1;
    case Group = 2;
    case Post = 3;
}
