<?php

return [

    // PM4: parts catalog browse. Counts/limits here — never hardcode.

    'per_page' => (int) env('PARTS_PER_PAGE', 24),

    // Offers shown on a part detail page before "show all" (PM5).
    'offers_per_part' => (int) env('PARTS_OFFERS_PER_PART', 20),

    // Frequently-bought-together suggestions (PM5, deterministic co-purchase).
    'fbt_count' => (int) env('PARTS_FBT_COUNT', 4),

];
