<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Vendor;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WalletAdjustmentController extends Controller
{
    public function __construct(private readonly WalletService $wallet) {}

    /**
     * Post a fully-audited manual ledger adjustment to a vendor's wallet.
     */
    public function store(Request $request, Vendor $vendor): RedirectResponse
    {
        $validated = $request->validate([
            'amount'    => ['required', 'numeric', 'min:0.01'],
            'direction' => ['required', 'in:credit,debit'],
            'reason'    => ['required', 'string', 'max:255'],
        ]);

        $this->wallet->post($this->wallet->walletFor($vendor), 'MANUAL_ADJUSTMENT', (float) $validated['amount'], [
            'direction'   => $validated['direction'],
            'created_by'  => $request->user()->id,
            'source_type' => 'admin_user',
            'source_id'   => $request->user()->id,
            'description' => $validated['reason'],
        ]);

        AuditLog::record($request->user(), 'wallet.manual_adjustment', $vendor, [
            'amount'    => $validated['amount'],
            'direction' => $validated['direction'],
            'reason'    => $validated['reason'],
        ]);

        return back()->with('status', 'Wallet adjusted.');
    }
}
