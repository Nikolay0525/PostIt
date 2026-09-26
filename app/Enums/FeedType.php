<?php

namespace App\Enums;

enum FeedType: string
{
    case Subscriptions = 'subscriptions';
    case Trending = 'trending';
}
