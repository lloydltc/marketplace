<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\TradeIn\Models\TradeIn;
use Illuminate\View\View;

/**
 * TI2: admin ops queue for trade-ins (manual-first shepherding — mirrors the
 * concierge admin queue rather than inventing new tooling).
 */
class TradeInController extends Controller
{
    public function index(): View
    {
        $tradeIns = TradeIn::with(['make', 'vehicleModel', 'user'])->withCount('offers')->latest()->paginate(25);

        return view('admin.trade-ins.index', compact('tradeIns'));
    }

    public function show(TradeIn $tradeIn): View
    {
        $tradeIn->load(['make', 'vehicleModel', 'user', 'photos', 'offers.vendor']);

        return view('admin.trade-ins.show', compact('tradeIn'));
    }
}
