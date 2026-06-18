<?php

namespace App\Modules\Vendors\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorBankAccount;
use App\Modules\Vendors\Services\VendorBankAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VendorBankAccountAdminController extends Controller
{
    public function __construct(
        private readonly VendorBankAccountService $bankService
    ) {}

    public function verify(Request $request, Vendor $vendor, VendorBankAccount $account): RedirectResponse
    {
        $this->authorize('verifyBankAccount', $vendor);

        abort_if($account->vendor_id !== $vendor->id, 404);

        $this->bankService->markVerified($account, $request->user());

        return back()->with('status', 'Bank account marked as verified.');
    }
}
