<?php

namespace App\Enums;

/**
 * The home page tabs. Recommended is the default for everyone; Following needs an account and is
 * split further by FeedSource.
 */
enum FeedType: string
{
    case Recommended = 'recommended';
    case Following = 'following';
}
