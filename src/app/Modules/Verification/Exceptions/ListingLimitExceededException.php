<?php

namespace App\Modules\Verification\Exceptions;

use RuntimeException;

class ListingLimitExceededException extends RuntimeException
{
    public function __construct(
        public readonly string $listingType,  // 'vehicle' or 'product'
        public readonly int    $limit,
        public readonly string $tier,
    ) {
        parent::__construct(
            "Listing limit reached. {$tier} accounts may have at most {$limit} {$listingType} listing(s). Upgrade to Premium for unlimited listings."
        );
    }
}
