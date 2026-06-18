<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Exceptions\PaymentGatewayException;
use App\Modules\Wallet\Models\Payout;
use App\Modules\Wallet\Services\TopUpService;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly TopUpService $topUps
    ) {}

    public function show(Request $request): View
    {
        $vendor = $request->attributes->get('vendor');
        $wallet = $this->wallet->walletFor($vendor);

        $entries    = $wallet->entries()->orderByDesc('created_at')->paginate(30);
        $nextPayout = Payout::where('vendor_id', $vendor->id)
            ->whereIn('status', ['pending', 'approved'])
            ->latest()
            ->first();

        return view('vendor.wallet.show', compact('wallet', 'entries', 'nextPayout', 'vendor'));
    }

    public function topUp(Request $request): RedirectResponse
    {
        $vendor    = $request->attributes->get('vendor');
        $validated = $request->validate(['amount' => ['required', 'numeric', 'min:1', 'max:100000']]);

        try {
            $topUp = $this->topUps->initiate(
                $vendor,
                (float) $validated['amount'],
                returnUrl: route('vendor.wallet.show'),
                resultUrl: route('payments.webhook'),
            );
        } catch (PaymentGatewayException) {
            return back()->withErrors(['amount' => 'Could not start the top-up. Please try again.']);
        }

        if (empty($topUp->redirect_url)) {
            return back()->withErrors(['amount' => 'The gateway did not return a payment link.']);
        }

        return redirect()->away($topUp->redirect_url);
    }
}
