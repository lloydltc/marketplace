<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Wallet\Models\Payout;
use App\Modules\Wallet\Services\PayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function __construct(private readonly PayoutService $payouts) {}

    public function index(): View
    {
        $payouts = Payout::with(['vendor', 'bankAccount'])->latest()->paginate(30);

        return view('admin.payouts.index', compact('payouts'));
    }

    public function generate(): RedirectResponse
    {
        $created = $this->payouts->generateWeeklyBatch();

        return back()->with('status', "Generated {$created->count()} pending payout(s).");
    }

    public function approve(Request $request, Payout $payout): RedirectResponse
    {
        $this->payouts->approve($payout, $request->user());

        return back()->with('status', "Payout for {$payout->vendor?->name} approved.");
    }

    public function markPaid(Request $request, Payout $payout): RedirectResponse
    {
        $validated = $request->validate(['reference' => ['required', 'string', 'max:120']]);
        $this->payouts->markPaid($payout, $validated['reference']);

        return back()->with('status', 'Payout marked as paid.');
    }

    public function reject(Payout $payout): RedirectResponse
    {
        $this->payouts->reject($payout);

        return back()->with('status', 'Payout rejected.');
    }
}
