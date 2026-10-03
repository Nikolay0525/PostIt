<?php

namespace App\Support;

use App\Enums\FeedPeriod;

/**
 * What the reader narrowed the home feed to — the same filters on every tab (Recommended,
 * Following → Groups / People). They only narrow a list; each feed keeps its own order.
 * Everything off by default, so without filters every feed is exactly as unfiltered. The first
 * two need an account: for a guest the caller passes them as false.
 */
final readonly class FeedFilters
{
    public function __construct(
        // Hide posts the user has opened (post_views) or voted on.
        public bool $onlyNew = false,
        // A strict filter on the user's speaking languages (on Recommended: instead of the
        // default language priority).
        public bool $onlyMyLanguages = false,
        public FeedPeriod $period = FeedPeriod::All,
    ) {}
}
