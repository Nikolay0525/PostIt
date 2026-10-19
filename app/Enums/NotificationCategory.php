<?php

namespace App\Enums;

enum NotificationCategory: int
{
    case Default = 0;
    case Important = 1;
}