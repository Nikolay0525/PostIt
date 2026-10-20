<?php

namespace App\Enums;

enum NotificationStatus: string
{
    case Enabled = 'enabled';
    case Muted = 'muted';
    case Disabled = 'disabled';
}
