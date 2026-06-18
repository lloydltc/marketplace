<?php

namespace App\Modules\Verification\Services;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Verification\Events\TierUpgradedEvent;
use App\Modules\Verification\Exceptions\ListingLimitExceededException;

class TierService
{
    public function upgradeVendorTier(Vendor $vendor, string $tier): void
    {
        $this->assertValidTier($tier);

        $previous = $vendor->tier;
        $vendor->update(['tier' => $tier]);

        TierUpgradedEvent::dispatch('vendor', $vendor->id, $tier, $previous);
    }

    public function upgradeSellerTier(User $user, string $tier): void
    {
        $this->assertValidTier($tier);

        $previous = $user->tier;
        $user->update(['tier' => $tier]);

        TierUpgradedEvent::dispatch('seller', $user->id, $tier, $previous);
    }

    // -------------------------------------------------------------------
    // Limit checks
    // -------------------------------------------------------------------

    public function assertCanCreateVehicleForVendor(Vendor $vendor): void
    {
        $limit = $this->vendorVehicleLimit($vendor);
        if ($limit === null) {
            return;
        }

        $count = $vendor->vehicles()->whereNull('deleted_at')->count();
        if ($count >= $limit) {
            throw new ListingLimitExceededException('vehicle', $limit, $vendor->tier);
        }
    }

    public function assertCanCreateVehicleForSeller(User $user): void
    {
        $limit = $this->sellerVehicleLimit($user);
        if ($limit === null) {
            return;
        }

        $count = $user->vehicles()->whereNull('deleted_at')->count();
        if ($count >= $limit) {
            throw new ListingLimitExceededException('vehicle', $limit, $user->tier);
        }
    }

    public function assertCanCreateProductForVendor(Vendor $vendor): void
    {
        $limit = $this->vendorProductLimit($vendor);
        if ($limit === null) {
            return;
        }

        $count = $vendor->products()->whereNull('deleted_at')->count();
        if ($count >= $limit) {
            throw new ListingLimitExceededException('product', $limit, $vendor->tier);
        }
    }

    // -------------------------------------------------------------------
    // Slot helpers (for displaying remaining capacity in UI)
    // -------------------------------------------------------------------

    public function vendorVehicleLimit(Vendor $vendor): ?int
    {
        return config("tiers.limits.vendor.{$vendor->tier}.vehicles");
    }

    public function vendorProductLimit(Vendor $vendor): ?int
    {
        return config("tiers.limits.vendor.{$vendor->tier}.products");
    }

    public function sellerVehicleLimit(User $user): ?int
    {
        return config("tiers.limits.seller.{$user->tier}.vehicles");
    }

    public function vendorRemainingVehicleSlots(Vendor $vendor): ?int
    {
        $limit = $this->vendorVehicleLimit($vendor);
        if ($limit === null) {
            return null;
        }

        $used = $vendor->vehicles()->whereNull('deleted_at')->count();
        return max(0, $limit - $used);
    }

    public function vendorRemainingProductSlots(Vendor $vendor): ?int
    {
        $limit = $this->vendorProductLimit($vendor);
        if ($limit === null) {
            return null;
        }

        $used = $vendor->products()->whereNull('deleted_at')->count();
        return max(0, $limit - $used);
    }

    public function sellerRemainingVehicleSlots(User $user): ?int
    {
        $limit = $this->sellerVehicleLimit($user);
        if ($limit === null) {
            return null;
        }

        $used = $user->vehicles()->whereNull('deleted_at')->count();
        return max(0, $limit - $used);
    }

    // -------------------------------------------------------------------
    // Image limit helpers
    // -------------------------------------------------------------------

    public function assertCanUploadVehicleImageForVendor(Vendor $vendor, int $currentCount): void
    {
        $limit = config("tiers.limits.vendor.{$vendor->tier}.vehicle_images");
        if ($limit !== null && $currentCount >= $limit) {
            throw new ListingLimitExceededException('vehicle image', $limit, $vendor->tier);
        }
    }

    public function assertCanUploadVehicleImageForSeller(User $user, int $currentCount): void
    {
        $limit = config("tiers.limits.seller.{$user->tier}.vehicle_images");
        if ($limit !== null && $currentCount >= $limit) {
            throw new ListingLimitExceededException('vehicle image', $limit, $user->tier);
        }
    }

    public function assertCanUploadProductImageForVendor(Vendor $vendor, int $currentCount): void
    {
        $limit = config("tiers.limits.vendor.{$vendor->tier}.product_images");
        if ($limit !== null && $currentCount >= $limit) {
            throw new ListingLimitExceededException('product image', $limit, $vendor->tier);
        }
    }

    public function vendorVehicleImageLimit(Vendor $vendor): ?int
    {
        return config("tiers.limits.vendor.{$vendor->tier}.vehicle_images");
    }

    public function vendorProductImageLimit(Vendor $vendor): ?int
    {
        return config("tiers.limits.vendor.{$vendor->tier}.product_images");
    }

    public function sellerVehicleImageLimit(User $user): ?int
    {
        return config("tiers.limits.seller.{$user->tier}.vehicle_images");
    }

    // -------------------------------------------------------------------

    private function assertValidTier(string $tier): void
    {
        $valid = config('tiers.tiers', ['unverified', 'premium']);
        if (!in_array($tier, $valid, true)) {
            throw new \InvalidArgumentException("Invalid tier: {$tier}");
        }
    }
}
