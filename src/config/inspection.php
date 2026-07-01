<?php

return [
    // TI3/TI5: inspection fee fallback (authoritative value in platform_settings
    // inspection.fee_usd). Standardized report checklist + verdicts (config-driven).
    'fee_usd' => (float) env('INSPECTION_FEE_USD', 30.00),

    'checklist' => [
        'Engine', 'Transmission & clutch', 'Brakes', 'Suspension & steering',
        'Tyres & wheels', 'Electrical & electronics', 'Body & paint', 'Interior', 'Road test',
    ],

    'verdicts' => [
        'pass'                 => 'Pass',
        'pass_with_advisories' => 'Pass with advisories',
        'fail'                 => 'Fail',
    ],
];
