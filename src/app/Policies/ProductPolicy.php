<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Products\Models\Product;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(?User $user, Product $product): bool
    {
        if ($product->isActive()) {
            return true;
        }

        // Vendor sees their own non-active products
        if ($user && $user->hasRole(['vendor_admin', 'vendor_worker'])) {
            return $user->vendor?->id === $product->vendor_id;
        }

        return $user?->hasRole(['super_admin', 'admin']) ?? false;
    }

    public function create(User $user): bool
    {
        // Vendors may build inventory while still pending (remediation R4/F13).
        // Listings show an "unverified" badge and are not transactable until
        // approval (gated at cart/checkout) — but creation itself is allowed.
        return $user->hasRole('vendor_admin') && $user->vendor !== null;
    }

    public function update(User $user, Product $product): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($user->hasRole('vendor_admin') && $user->vendor?->id === $product->vendor_id) {
            return $product->canBeEditedByVendor();
        }

        return false;
    }

    public function delete(User $user, Product $product): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        return $user->hasRole('vendor_admin') && $user->vendor?->id === $product->vendor_id;
    }

    public function approve(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function reject(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }
}
