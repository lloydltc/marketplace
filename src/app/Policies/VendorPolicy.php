<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function view(User $user, Vendor $vendor): bool
    {
        return true;
    }

    public function update(User $user, Vendor $vendor): bool
    {
        if (in_array($user->role, ['admin', 'super_admin'], true)) {
            return true;
        }

        return $user->isVendorAdmin() && $user->belongsToVendor($vendor->id);
    }

    public function uploadDocument(User $user, Vendor $vendor): bool
    {
        return $user->isVendorAdmin() && $user->belongsToVendor($vendor->id);
    }

    public function manageBankAccounts(User $user, Vendor $vendor): bool
    {
        return $user->isVendorAdmin() && $user->belongsToVendor($vendor->id);
    }

    public function approve(User $user, Vendor $vendor): bool
    {
        return in_array($user->role, ['admin', 'super_admin'], true);
    }

    public function reject(User $user, Vendor $vendor): bool
    {
        return in_array($user->role, ['admin', 'super_admin'], true);
    }

    public function suspend(User $user, Vendor $vendor): bool
    {
        return in_array($user->role, ['admin', 'super_admin'], true);
    }

    public function verifyBankAccount(User $user): bool
    {
        return in_array($user->role, ['admin', 'super_admin'], true);
    }
}
