<?php

namespace App\Enums;

enum VoteParentType: int
{
    case Post = 1;
    case Comment = 2;
}
