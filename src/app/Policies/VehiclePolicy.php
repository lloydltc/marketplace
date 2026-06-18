<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Vehicles\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Vehicle $vehicle): bool
    {
        if ($vehicle->isActive()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($user->hasRole(['vendor_admin', 'vendor_worker'])) {
            return $user->vendor?->id === $vehicle->vendor_id;
        }

        if ($user->hasRole('private_seller')) {
            return $user->id === $vehicle->user_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Sellers may list while pending (remediation R4/F13); listings carry an
        // "unverified" badge until approved. Vehicles are lead-gen (no checkout),
        // so there is no transaction to gate here.
        if ($user->hasRole('vendor_admin')) {
            return $user->vendor !== null;
        }

        return $user->hasRole('private_seller');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        if (! $vehicle->canBeEdited()) {
            return false;
        }

        if ($user->hasRole('vendor_admin')) {
            return $user->vendor?->id === $vehicle->vendor_id;
        }

        if ($user->hasRole('private_seller')) {
            return $user->id === $vehicle->user_id;
        }

        return false;
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($user->hasRole('vendor_admin')) {
            return $user->vendor?->id === $vehicle->vendor_id;
        }

        if ($user->hasRole('private_seller')) {
            return $user->id === $vehicle->user_id;
        }

        return false;
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
