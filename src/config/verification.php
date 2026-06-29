<?php

return [

    /*
    | VB1: trust-badge tiers + verification dimensions + reputation weights.
    | All criteria are config-driven (no hardcoding); tier eligibility is a rule
    | set over approved verification dimensions + reputation + manual grants.
    */

    // Verification dimensions a vendor can clear (evidence handled by VendorDocument/bank flows).
    'dimensions' => ['company_reg', 'tax', 'location', 'identity', 'banking'],

    // How long an approved dimension stays valid before re-verification (months). 0 = never expires.
    'dimension_expiry_months' => (int) env('VERIFICATION_DIMENSION_EXPIRY_MONTHS', 12),

    /*
    | Badge tiers. `rank` orders them (highest rank = primary badge shown).
    | A vendor earns a tier when: all `required_dimensions` are approved & unexpired,
    | reputation ≥ `min_reputation` (if set), and — for `manual_only` tiers — it has
    | been granted manually by an admin.
    */
    'tiers' => [
        'verified_dealer' => [
            'label' => 'Verified Dealer', 'icon' => '✓', 'rank' => 1,
            'required_dimensions' => ['company_reg', 'banking'],
            'min_reputation' => null, 'manual_only' => false,
        ],
        'trusted_seller' => [
            'label' => 'Trusted Seller', 'icon' => '🛡', 'rank' => 2,
            'required_dimensions' => ['company_reg', 'identity', 'banking'],
            'min_reputation' => null, 'manual_only' => false,
        ],
        'premium_dealer' => [
            'label' => 'Premium Dealer', 'icon' => '★', 'rank' => 3,
            'required_dimensions' => ['company_reg', 'tax', 'banking', 'location'],
            'min_reputation' => null, 'manual_only' => false,
        ],
        'top_rated' => [
            'label' => 'Top-Rated', 'icon' => '🏆', 'rank' => 4,
            'required_dimensions' => ['company_reg', 'banking'],
            'min_reputation' => (int) env('VERIFICATION_TOP_RATED_MIN', 80), 'manual_only' => false,
        ],
        'manufacturer_authorized' => [
            'label' => 'Manufacturer-Authorized', 'icon' => '🏭', 'rank' => 5,
            'required_dimensions' => [], 'min_reputation' => null, 'manual_only' => true,
        ],
    ],

    // VB3: reputation score (0–100) component weights — must sum to ~1.0.
    'reputation' => [
        'weights' => [
            'rating'       => (float) env('REP_W_RATING', 0.30),       // avg listing rating (when present)
            'response'     => (float) env('REP_W_RESPONSE', 0.20),     // lead response rate
            'conversion'   => (float) env('REP_W_CONVERSION', 0.20),   // lead → sale
            'disputes'     => (float) env('REP_W_DISPUTES', 0.20),     // low cancel/refund rate
            'quality'      => (float) env('REP_W_QUALITY', 0.10),      // listing completeness
        ],
        // Subtle search-ranking boost per reputation point (config-driven; 0 disables).
        'ranking_boost_per_point' => (float) env('REP_RANKING_BOOST', 0.0),
    ],
];
