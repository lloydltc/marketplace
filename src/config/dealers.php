<?php

return [

    // H8: public dealer directory + storefronts. Counts/limits live here — never
    // hardcode them in controllers or views.

    // How many featured (paid-placement) dealers to surface in the carousel.
    'featured_count' => (int) env('DEALERS_FEATURED_COUNT', 8),

    // Dealers per page in the "Find a Dealer" directory.
    'per_page' => (int) env('DEALERS_PER_PAGE', 24),

    // Listings shown per section on a storefront before "view all".
    'storefront_listings' => (int) env('DEALERS_STOREFRONT_LISTINGS', 12),

];
