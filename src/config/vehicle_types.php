<?php

/*
|--------------------------------------------------------------------------
| Listing types (H0) — Vehicle / Motorbike / Boat / Trailer
|--------------------------------------------------------------------------
|
| Type determines which body-types, specs, and features apply to a listing.
| Single source of truth for the type chooser, editor, browse tabs, and
| type-scoped validation. `vehicle` (cars) is the default for existing rows.
|
*/

return [
    'default' => 'vehicle',

    'types' => [
        'vehicle' => [
            'label'  => 'Car',
            'plural' => 'Cars',
            'icon'   => '🚗',
            'body_types' => ['sedan', 'hatchback', 'suv', 'pickup', 'van', 'minivan', 'wagon', 'coupe', 'convertible', 'bus', 'truck', 'other'],
        ],
        'motorbike' => [
            'label'  => 'Motorbike',
            'plural' => 'Bikes',
            'icon'   => '🏍️',
            'body_types' => ['standard', 'cruiser', 'sport', 'scooter', 'off_road', 'touring', 'other'],
        ],
        'boat' => [
            'label'  => 'Boat',
            'plural' => 'Boats',
            'icon'   => '🚤',
            'body_types' => ['speedboat', 'fishing', 'pontoon', 'sailboat', 'jetski', 'yacht', 'other'],
        ],
        'trailer' => [
            'label'  => 'Trailer',
            'plural' => 'Trailers',
            'icon'   => '🚛',
            'body_types' => ['utility', 'enclosed', 'flatbed', 'boat_trailer', 'car_trailer', 'livestock', 'other'],
        ],
    ],
];
