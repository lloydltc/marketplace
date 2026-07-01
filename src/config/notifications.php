<?php

return [

    /*
    | AC1: shared notification architecture config. Channels and per-type defaults
    | are config-driven; no hardcoding in notification classes.
    */

    // Channels the platform can deliver on. Push is gated until infra exists.
    'channels' => [
        'in_app' => ['enabled' => true,  'label' => 'In-app'],
        'email'  => ['enabled' => true,  'label' => 'Email'],
        'push'   => ['enabled' => (bool) env('NOTIFICATIONS_PUSH_ENABLED', false), 'label' => 'Push'],
    ],

    // Per notification type: which channels are on by default (a user can override
    // via notification_preferences) and whether it batches into a digest.
    'types' => [
        'alert.new_match'    => ['label' => 'New listings matching a saved search', 'default' => ['in_app', 'email'], 'digestable' => true],
        'alert.price_drop'   => ['label' => 'Price drops',                          'default' => ['in_app', 'email'], 'digestable' => true],
        'alert.similar'      => ['label' => 'Similar vehicles',                     'default' => ['in_app'],          'digestable' => true],
        'alert.dealer_promo' => ['label' => 'Dealer promotions',                    'default' => ['in_app'],          'digestable' => true],
        'listing.lifecycle'  => ['label' => 'Your listing updates',                 'default' => ['in_app', 'email'], 'digestable' => false],
        'verification'       => ['label' => 'Verification & badges',                'default' => ['in_app', 'email'], 'digestable' => false],
        'trade_in.submitted' => ['label' => 'New trade-in submissions (dealers)',   'default' => ['in_app', 'email'], 'digestable' => false],
        'trade_in.offer'     => ['label' => 'Trade-in offer updates',               'default' => ['in_app', 'email'], 'digestable' => false],
        'inspection'         => ['label' => 'Inspection updates',                   'default' => ['in_app', 'email'], 'digestable' => false],
    ],

    // Saved-search / digest cadence.
    'digest' => [
        'cadence' => env('NOTIFICATIONS_DIGEST_CADENCE', 'daily'), // daily | weekly
    ],
];
