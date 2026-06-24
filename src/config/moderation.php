<?php

return [

    // H11: listing moderation. Reasons, auto-flag rules, and thresholds live here —
    // no AI, all deterministic and config-driven.

    // Reasons a buyer can pick when reporting a listing.
    'reasons' => [
        'scam'        => 'Looks like a scam / fraud',
        'sold'        => 'Already sold / unavailable',
        'misleading'  => 'Misleading or wrong information',
        'prohibited'  => 'Prohibited or illegal item',
        'duplicate'   => 'Duplicate listing',
        'offensive'   => 'Offensive or inappropriate',
        'other'       => 'Something else',
    ],

    // Rule-based auto-flag config.
    'auto' => [
        // Title/description keywords that flag a listing for review (case-insensitive).
        'banned_keywords' => array_filter(array_map('trim', explode(',', (string) env(
            'MODERATION_BANNED_KEYWORDS',
            'stolen,replica,fake,counterfeit,clone,money doubling,western union only'
        )))),

        // Vehicles priced (USD) at or below this are flagged as likely scam/typo.
        // 0 disables the rule.
        'min_reasonable_vehicle_usd' => (int) env('MODERATION_MIN_VEHICLE_USD', 100),
    ],

];
