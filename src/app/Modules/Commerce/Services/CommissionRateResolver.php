<?php

namespace App\Modules\Commerce\Services;

use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Settings\Services\SettingsService;

/**
 * Resolves the commission rate (%) that applies to a parts sale, in the order
 * mandated by BUSINESS_MODEL.md §5 (first match wins):
 *
 *   1. Per-vendor override   (vendors.commission_rate)    — Phase 3
 *   2. Per-category override (categories.commission_override) — Phase 4
 *   3. Platform default      (platform_settings: commission.default_rate)
 *
 * This only resolves the RATE. The full commission engine (Phase 11) snapshots
 * the resolved rate and computed amounts onto the order at order time so that
 * historical orders are unaffected by later rate changes.
 */
class CommissionRateResolver
{
    public function __construct(private readonly SettingsService $settings) {}

    public function resolve(?Vendor $vendor = null, ?Category $category = null): float
    {
        if ($vendor !== null && $vendor->commission_rate !== null) {
            return (float) $vendor->commission_rate;
        }

        if ($category !== null && $category->commission_override !== null) {
            return (float) $category->commission_override;
        }

        return $this->platformDefault();
    }

    public function platformDefault(): float
    {
        return $this->settings->getDecimal('commission.default_rate', 10.0);
    }
}
