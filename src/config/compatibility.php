<?php

return [

    // H10: parts ⇄ vehicle cross-sell. Counts live here — never hardcode them.

    // "Parts that fit this vehicle" shown on a vehicle detail page.
    'parts_per_vehicle' => (int) env('COMPAT_PARTS_PER_VEHICLE', 8),

    // "Compatible vehicles for sale" shown on a part detail page.
    'vehicles_per_part' => (int) env('COMPAT_VEHICLES_PER_PART', 6),

];
