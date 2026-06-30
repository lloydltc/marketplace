<?php

namespace App\Http\Controllers;

use App\Modules\History\Models\HistoryReport;
use App\Modules\History\Services\HistoryPurchaseService;
use App\Modules\History\Services\ReportAssembler;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * HR3: buyer-facing history report — free preview, purchase (Pesepay), full view
 * (print-to-PDF), and a purchased-reports list. Honest "unavailable" states; never
 * fabricated.
 */
class HistoryReportController extends Controller
{
    public function __construct(private readonly ReportAssembler $assembler) {}

    /** Free preview (public). Shows preview sections; the rest unlock on purchase. */
    public function preview(Request $request, Vehicle $vehicle): View
    {
        abort_unless($vehicle->isActive(), 404);

        $report = $this->assembler->assembleFor($vehicle, $request->user()?->id);
        $previewTypes = (array) config('history.preview_types', []);

        return view('history.preview', [
            'vehicle'      => $vehicle,
            'report'       => $report,
            'previewTypes' => $previewTypes,
        ]);
    }

    /** Begin purchase (customer). Free reports unlock immediately. */
    public function purchase(Request $request, Vehicle $vehicle, HistoryPurchaseService $purchase): RedirectResponse
    {
        abort_unless($vehicle->isActive(), 404);

        $report = $this->assembler->assembleFor($vehicle, $request->user()->id);

        $redirect = $purchase->initiate(
            $report,
            route('history.show', $report),
            route('payments.webhook'),
        );

        return $redirect ? redirect()->away($redirect) : redirect()->route('history.show', $report)
            ->with('status', 'Your history report is ready.');
    }

    /** Gateway return — confirm payment, then show. */
    public function paymentReturn(Request $request, HistoryReport $report, HistoryPurchaseService $purchase): RedirectResponse
    {
        $this->authorizeOwner($request, $report);
        $purchase->confirm($report);

        return redirect()->route('history.show', $report);
    }

    /** Full report (owner, purchased). */
    public function show(Request $request, HistoryReport $report): View
    {
        $this->authorizeOwner($request, $report);
        abort_unless($report->isPurchased(), 402, 'This report has not been purchased.');

        $report->load(['sections', 'vehicle.make', 'vehicle.vehicleModel']);

        return view('history.show', compact('report'));
    }

    /** Buyer's purchased reports. */
    public function index(Request $request): View
    {
        $reports = HistoryReport::where('requested_by', $request->user()->id)
            ->where('status', 'purchased')
            ->with('vehicle.make', 'vehicle.vehicleModel')
            ->latest('purchased_at')
            ->paginate(20);

        return view('history.index', compact('reports'));
    }

    private function authorizeOwner(Request $request, HistoryReport $report): void
    {
        abort_unless($report->requested_by === $request->user()->id, 403);
    }
}
