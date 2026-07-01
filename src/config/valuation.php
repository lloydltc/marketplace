<?php

return [

    /*
    | TI1: trade-in valuation from comparable listings (NO AI — transparent math).
    | An estimate range, never an offer.
    */

    'comparables_year_band' => (int) env('VALUATION_YEAR_BAND', 2),   // ± years
    'min_comparables'       => (int) env('VALUATION_MIN_COMPARABLES', 3),
    'range_spread'          => (float) env('VALUATION_RANGE_SPREAD', 0.10), // ±10% around adjusted base

    // Price adjusted down/up when the trade-in's mileage is above/below the
    // comparable average — per 10,000 km.
    'mileage_adjust_per_10k' => (float) env('VALUATION_MILEAGE_ADJ', 0.03),
    'max_mileage_adjust'     => (float) env('VALUATION_MAX_MILEAGE_ADJ', 0.30),

    'condition_factors' => [
        'excellent' => 1.05,
        'good'      => 1.00,
        'fair'      => 0.90,
        'poor'      => 0.75,
    ],
];
