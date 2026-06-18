<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Delivery\Models\RiderCashSession;
use App\Modules\Delivery\Services\CashReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashSessionController extends Controller
{
    public function __construct(private readonly CashReconciliationService $reconciliation) {}

    public function index(): View
    {
        $sessions = RiderCashSession::with('rider')
            ->where('expected_total', '>', 0)
            ->latest('session_date')
            ->paginate(30);

        return view('admin.cash-sessions.index', compact('sessions'));
    }

    public function reconcile(Request $request, RiderCashSession $session): RedirectResponse
    {
        $validated = $request->validate(['collected_total' => ['required', 'numeric', 'min:0']]);

        $this->reconciliation->reconcile($session, (float) $validated['collected_total'], $request->user());

        return back()->with('status', 'Cash session reconciled.');
    }

    public function resolve(Request $request, RiderCashSession $session): RedirectResponse
    {
        $this->reconciliation->resolve($session, $request->user());

        return back()->with('status', 'Discrepancy resolved and orders settled.');
    }
}
