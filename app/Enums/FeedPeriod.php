<?php

namespace App\Enums;

/**
 * The Recommended tab's period filter. All (the default) keeps every post, freshest first.
 */
enum FeedPeriod: string
{
    case Week = 'week';
    case Month = 'month';
    case All = 'all';

    // How far back posts may go; null = no limit.
    public function days(): ?int
    {
        return match ($this) {
            self::Week => 7,
            self::Month => 30,
            self::All => null,
        };
    }
}
