<?php

namespace App\Modules\Vendors\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorBankAccount;
use App\Modules\Vendors\Requests\Vendor\StoreBankAccountRequest;
use App\Modules\Vendors\Services\VendorBankAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public function __construct(
        private readonly VendorBankAccountService $bankService
    ) {}

    public function index(Request $request): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        $vendor->load('bankAccounts');

        return view('vendor.bank-accounts.index', compact('vendor'));
    }

    public function store(StoreBankAccountRequest $request): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        $this->bankService->store($vendor, $request->validated());

        return redirect()
            ->route('vendor.bank-accounts.index')
            ->with('status', 'Bank account added. Awaiting admin verification.');
    }

    public function destroy(Request $request, VendorBankAccount $account): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null || $account->vendor_id !== $vendor->id, 404);

        $this->authorize('manageBankAccounts', $vendor);

        $this->bankService->remove($account);

        return redirect()
            ->route('vendor.bank-accounts.index')
            ->with('status', 'Bank account removed.');
    }
}
