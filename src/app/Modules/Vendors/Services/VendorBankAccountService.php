<?php

namespace App\Modules\Vendors\Services;

use App\Models\Vendor;
use App\Models\VendorBankAccount;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class VendorBankAccountService
{
    /**
     * Store a new bank account for a vendor.
     */
    public function store(Vendor $vendor, array $data): VendorBankAccount
    {
        return $vendor->bankAccounts()->create($data);
    }

    /**
     * Mark a bank account as verified (admin action).
     */
    public function markVerified(VendorBankAccount $account, User $admin): void
    {
        $account->update(['verified_at' => now()]);

        Log::info('Bank account verified', [
            'account_id' => $account->id,
            'vendor_id'  => $account->vendor_id,
            'by'         => $admin->id,
        ]);
    }

    /**
     * Delete a bank account (only if unverified).
     */
    public function remove(VendorBankAccount $account): void
    {
        abort_if(
            $account->isVerified(),
            422,
            'Verified bank accounts cannot be removed. Contact support.'
        );

        $account->delete();
    }
}
