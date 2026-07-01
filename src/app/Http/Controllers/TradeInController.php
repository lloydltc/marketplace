<?php

namespace App\Http\Controllers;

use App\Modules\TradeIn\Models\TradeIn;
use App\Modules\TradeIn\Services\ValuationService;
use App\Modules\Vehicles\Models\VehicleMake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * TI1: buyer trade-in submission → transparent comparable-listing estimate range
 * (an estimate, not an offer). Manual-first; dealer bidding follows in TI2.
 */
class TradeInController extends Controller
{
    public function index(Request $request): View
    {
        $tradeIns = TradeIn::where('user_id', $request->user()->id)
            ->with(['make', 'vehicleModel'])->withCount('offers')->latest()->paginate(20);

        return view('trade-ins.index', compact('tradeIns'));
    }

    public function create(): View
    {
        return view('trade-ins.create', [
            'makes'      => VehicleMake::with('models')->orderBy('name')->get(),
            'conditions' => array_keys(config('valuation.condition_factors', [])),
        ]);
    }

    public function store(Request $request, ValuationService $valuation): RedirectResponse
    {
        $validated = $request->validate([
            'make_id'   => ['required', 'uuid', 'exists:vehicle_makes,id'],
            'model_id'  => ['required', 'uuid', 'exists:vehicle_models,id'],
            'year'      => ['required', 'integer', 'min:1900', 'max:2100'],
            'mileage'   => ['required', 'integer', 'min:0', 'max:2000000'],
            'condition' => ['required', 'in:' . implode(',', array_keys(config('valuation.condition_factors', [])))],
            'notes'     => ['nullable', 'string', 'max:1000'],
            'photos'    => ['nullable', 'array', 'max:8'],
            'photos.*'  => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
        ]);

        $estimate = $valuation->estimate($validated['make_id'], $validated['model_id'], $validated['year'], $validated['mileage'], $validated['condition']);

        $tradeIn = TradeIn::create([
            'user_id'             => $request->user()->id,
            'make_id'             => $validated['make_id'],
            'model_id'            => $validated['model_id'],
            'year'                => $validated['year'],
            'mileage'             => $validated['mileage'],
            'condition'           => $validated['condition'],
            'notes'               => $validated['notes'] ?? null,
            'estimate_low_minor'  => $estimate['low_minor'] ?? null,
            'estimate_high_minor' => $estimate['high_minor'] ?? null,
            'comparables_count'   => $estimate['comparables'] ?? 0,
            'status'              => 'valued',
        ]);

        foreach ($request->file('photos', []) as $photo) {
            $path = $photo->store('trade-ins/' . $tradeIn->id, 'public');
            $tradeIn->photos()->create(['disk' => 'public', 'path' => $path]);
        }

        return redirect()->route('trade-ins.show', $tradeIn)
            ->with('status', $estimate ? 'Here is your estimate.' : 'Submitted — we need more comparable listings to estimate; a dealer may still bid.');
    }

    public function show(Request $request, TradeIn $tradeIn): View
    {
        abort_unless($tradeIn->user_id === $request->user()->id, 403);
        $tradeIn->load(['make', 'vehicleModel', 'photos', 'offers.vendor']);

        return view('trade-ins.show', compact('tradeIn'));
    }

    /** TI2: buyer accepts a dealer's offer; the rest are declined; handoff recorded. */
    public function acceptOffer(Request $request, TradeIn $tradeIn, \App\Modules\TradeIn\Models\TradeInOffer $offer): RedirectResponse
    {
        abort_unless($tradeIn->user_id === $request->user()->id, 403);
        abort_unless($offer->trade_in_id === $tradeIn->id, 404);

        $offer->update(['status' => 'accepted']);
        $tradeIn->offers()->where('id', '!=', $offer->id)->update(['status' => 'declined']);
        $tradeIn->update(['status' => 'accepted', 'accepted_offer_id' => $offer->id]);

        \App\Models\AuditLog::record($request->user(), 'trade_in.offer.accepted', $tradeIn, [
            'offer_id' => $offer->id, 'vendor_id' => $offer->vendor_id, 'amount_minor' => $offer->amount_minor,
        ]);

        return back()->with('status', 'Offer accepted — the dealer will be in touch.');
    }
}
