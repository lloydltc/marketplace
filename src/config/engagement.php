<?php

return [

    // H7: buyer engagement surfaces. All counts/limits live here — never hardcode
    // them in controllers or views.

    'compare' => [
        // Maximum vehicles a buyer can line up side-by-side at once.
        'max_items' => (int) env('ENGAGEMENT_COMPARE_MAX', 4),
    ],

    'recently_viewed' => [
        // How many recently-viewed vehicles to remember (per browser, cookie-backed).
        'max' => (int) env('ENGAGEMENT_RECENTLY_VIEWED_MAX', 10),
        // Cookie lifetime in days.
        'cookie_days' => (int) env('ENGAGEMENT_RECENTLY_VIEWED_DAYS', 30),
    ],

    'sponsored' => [
        // How many sponsored (featured) listings to surface in promo rows.
        'count' => (int) env('ENGAGEMENT_SPONSORED_COUNT', 4),
    ],

    'alerts' => [
        // Cap on listings enumerated per saved-search email so a long-dormant
        // alert can't produce an unbounded digest.
        'max_per_email' => (int) env('ENGAGEMENT_ALERT_MAX_PER_EMAIL', 12),
    ],

];
