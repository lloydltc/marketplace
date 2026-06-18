<?php

namespace App\Modules\Vendors\Services;

use App\Models\Vendor;
use App\Modules\Vendors\Repositories\VendorRepositoryInterface;
use InvalidArgumentException;

class VendorTierService
{
    private const TIER_ORDER = ['bronze', 'silver', 'gold', 'platinum'];

    private const LISTING_LIMITS = [
        'bronze'   => 10,
        'silver'   => 100,
        'gold'     => PHP_INT_MAX,
        'platinum' => PHP_INT_MAX,
    ];

    private const COMMISSION_RATES = [
        'bronze'   => 10.00,
        'silver'   => 8.00,
        'gold'     => 5.00,
        'platinum' => 3.00,
    ];

    public function __construct(
        private readonly VendorRepositoryInterface $repository
    ) {}

    /**
     * Upgrade a vendor to the next tier (or a specified higher tier).
     */
    public function upgrade(Vendor $vendor, string $newTier): void
    {
        $this->validateTierTransition($vendor->tier, $newTier, 'upgrade');

        $this->repository->update($vendor, [
            'tier'            => $newTier,
            'commission_rate' => self::COMMISSION_RATES[$newTier],
        ]);
    }

    /**
     * Downgrade a vendor to a lower tier.
     */
    public function downgrade(Vendor $vendor, string $newTier): void
    {
        $this->validateTierTransition($vendor->tier, $newTier, 'downgrade');

        $this->repository->update($vendor, [
            'tier'            => $newTier,
            'commission_rate' => self::COMMISSION_RATES[$newTier],
        ]);
    }

    /**
     * Return the maximum number of active listings for a vendor.
     */
    public function getListingLimit(Vendor $vendor): int
    {
        return self::LISTING_LIMITS[$vendor->tier] ?? 10;
    }

    /**
     * Return the commission rate for a vendor.
     */
    public function getCommissionRate(Vendor $vendor): float
    {
        return self::COMMISSION_RATES[$vendor->tier] ?? 10.00;
    }

    /**
     * Return all tier definitions for display.
     *
     * @return array<string, array{listings: int|string, commission: float, fee: int}>
     */
    public function getTierDefinitions(): array
    {
        return [
            'bronze'   => ['listings' => 10,           'commission' => 10.00, 'fee' => 0],
            'silver'   => ['listings' => 100,           'commission' => 8.00,  'fee' => 50],
            'gold'     => ['listings' => 'Unlimited',   'commission' => 5.00,  'fee' => 200],
            'platinum' => ['listings' => 'Unlimited',   'commission' => 3.00,  'fee' => 500],
        ];
    }

    private function validateTierTransition(string $current, string $target, string $direction): void
    {
        if (! in_array($target, self::TIER_ORDER, true)) {
            throw new InvalidArgumentException("Invalid tier: {$target}");
        }

        $currentIndex = array_search($current, self::TIER_ORDER, true);
        $targetIndex  = array_search($target, self::TIER_ORDER, true);

        if ($direction === 'upgrade' && $targetIndex <= $currentIndex) {
            throw new InvalidArgumentException("Cannot upgrade from {$current} to {$target}.");
        }

        if ($direction === 'downgrade' && $targetIndex >= $currentIndex) {
            throw new InvalidArgumentException("Cannot downgrade from {$current} to {$target}.");
        }
    }
}
