<?php

return [

    /*
    | HR1: vehicle history reports. Per-report price comes from platform_settings
    | (history.report_price_usd); this fallback applies if the setting is absent.
    | The sources registry declares each pluggable adapter and its availability —
    | honest gating, never fabrication.
    */

    'report_price_usd' => (float) env('HISTORY_REPORT_PRICE_USD', 5.00),

    // Free preview shows these section types; the rest unlock on purchase.
    'preview_types' => ['import', 'ownership'],

    'sources' => [
        'import' => [
            'name' => 'Import record', 'type' => 'import',
            'adapter' => \App\Modules\History\Adapters\ImportRecordAdapter::class, 'status' => 'live',
        ],
        'platform' => [
            'name' => 'SalmaDrive listing & ownership history', 'type' => 'ownership',
            'adapter' => \App\Modules\History\Adapters\PlatformHistoryAdapter::class, 'status' => 'live',
        ],
        'odometer' => [
            'name' => 'Odometer (seller-declared)', 'type' => 'odometer',
            'adapter' => \App\Modules\History\Adapters\OdometerAdapter::class, 'status' => 'live',
        ],
        'service' => [
            'name' => 'Service history (dealer-supplied)', 'type' => 'service',
            'adapter' => \App\Modules\History\Adapters\ServiceHistoryAdapter::class, 'status' => 'manual',
        ],
        // Gated — registered so they render honestly as "not available yet".
        'registration'     => ['name' => 'Registration / ZINARA',      'type' => 'registration',     'adapter' => null, 'status' => 'unavailable'],
        'police_clearance' => ['name' => 'Police clearance (ZRP)',     'type' => 'police_clearance', 'adapter' => null, 'status' => 'unavailable'],
        'roadworthiness'   => ['name' => 'Roadworthiness (VID)',       'type' => 'roadworthiness',   'adapter' => null, 'status' => 'unavailable'],
        'insurance'        => ['name' => 'Insurance / accident',       'type' => 'insurance',        'adapter' => null, 'status' => 'unavailable'],
    ],
];
