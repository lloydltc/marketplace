<?php

return [

    // H7: buyer engagement surfaces. All counts/limits live here — never hardcode
    // them in controllers or views.

    'compare' => [
        // Maximum vehicles a buyer can line up side-by-side at once (AC3: up to 5).
        'max_items' => (int) env('ENGAGEMENT_COMPARE_MAX', 5),

        // AC3: deterministic running-cost estimate inputs (no AI). Used for the
        // "Est. fuel cost" comparison row.
        'fuel_cost' => [
            'annual_km'           => (int) env('COMPARE_ANNUAL_KM', 15000),
            'price_per_litre_usd' => (float) env('COMPARE_FUEL_PRICE', 1.50),
            'years'               => (int) env('COMPARE_FUEL_YEARS', 5),
            'l_per_100km'         => [
                'petrol' => 9.0, 'diesel' => 7.0, 'hybrid' => 5.0, 'electric' => 0.0, 'other' => 9.0,
            ],
        ],
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
