<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Modules\TradeIn\Models\TradeIn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * TI2: dealer trade-in bidding portal. Verified/approved dealers view open
 * submissions and place (or update) a firm offer. Manual-first — no auto-matching.
 */
class TradeInBidController extends Controller
{
    public function index(Request $request): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        $tradeIns = TradeIn::query()
            ->whereIn('status', ['new', 'valued', 'bidding'])
            ->with(['make', 'vehicleModel', 'photos', 'offers' => fn ($q) => $q->where('vendor_id', $vendor->id)])
            ->latest()
            ->paginate(20);

        return view('vendor.trade-ins.index', compact('tradeIns'));
    }

    public function bid(Request $request, TradeIn $tradeIn): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);
        abort_unless($vendor->isApproved(), 403, 'Only approved dealers can bid.');
        abort_if(in_array($tradeIn->status, ['accepted', 'closed', 'cancelled'], true), 422, 'This trade-in is closed.');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:99999999'],
            'notes'  => ['nullable', 'string', 'max:500'],
        ]);

        $tradeIn->offers()->updateOrCreate(
            ['vendor_id' => $vendor->id],
            [
                'amount_minor' => (int) round($validated['amount'] * 100),
                'currency'     => 'USD',
                'notes'        => $validated['notes'] ?? null,
                'status'       => 'offered',
                'expires_at'   => now()->addDays(7),
            ],
        );

        if (in_array($tradeIn->status, ['new', 'valued'], true)) {
            $tradeIn->update(['status' => 'bidding']);
        }

        return back()->with('status', 'Your offer has been sent to the seller.');
    }
}
