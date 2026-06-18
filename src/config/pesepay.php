<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pesepay Payment Gateway
    |--------------------------------------------------------------------------
    |
    | SalmaDrive operates a collect-then-distribute model: all gateway funds
    | land in the platform account; vendors are settled later via the wallet
    | payout cycle (Phase 13). No split payments happen at the gateway.
    |
    | Keys are environment-specific and must never be committed.
    */

    'integration_key' => env('PESEPAY_INTEGRATION_KEY'),

    // 32-character AES key used to encrypt/decrypt payloads.
    'encryption_key' => env('PESEPAY_ENCRYPTION_KEY'),

    'base_url' => env('PESEPAY_BASE_URL', 'https://api.pesepay.com/api/payments-engine'),

    'default_currency' => env('PESEPAY_DEFAULT_CURRENCY', 'USD'),

    'endpoints' => [
        'initiate'     => '/v1/payments/initiate',       // hosted redirect flow
        'make_payment' => '/v2/payments/make-payment',   // seamless mobile-money flow
        'check'        => '/v1/payments/check-payment',
    ],

    /*
    | Seamless payment-method codes. These are CURRENCY-SPECIFIC and assigned by
    | Pesepay — confirm them for your account/currency via the
    | get-payment-methods endpoint or the Pesepay dashboard. Defaults below are
    | the common ZWL codes (EcoCash USD is typically PZW201).
    */
    'methods' => [
        'ecocash'  => env('PESEPAY_METHOD_ECOCASH', 'PZW211'),
        'innbucks' => env('PESEPAY_METHOD_INNBUCKS', 'PZW212'),
    ],
];
