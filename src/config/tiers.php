<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Seller Verification Tiers
    |--------------------------------------------------------------------------
    |
    | Listing limits per tier. null means unlimited.
    | 'vehicles' applies to both vendor and private_seller contexts.
    | 'products' only applies to vendors.
    |
    */

    'tiers' => ['unverified', 'premium'],

    'limits' => [
        'vendor' => [
            'unverified' => [
                'vehicles'        => 10,
                'products'        => 20,
                'vehicle_images'  => 5,
                'product_images'  => 5,
            ],
            'premium' => [
                'vehicles'        => null,
                'products'        => null,
                'vehicle_images'  => 20,
                'product_images'  => 20,
            ],
        ],

        'seller' => [
            'unverified' => [
                'vehicles'       => 3,
                'vehicle_images' => 5,
            ],
            'premium' => [
                'vehicles'       => null,
                'vehicle_images' => 20,
            ],
        ],
    ],
];
