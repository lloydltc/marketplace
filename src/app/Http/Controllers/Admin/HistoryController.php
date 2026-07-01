<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Modules\History\Models\HistoryDataSource;
use App\Modules\History\Models\HistoryReport;
use App\Modules\History\Services\HistoryPurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * HR4: admin management of history data sources, manual section entry (e.g. dealer
 * service records), and report refunds. All privileged actions audited (R6).
 * Per-report pricing lives in platform_settings (history.report_price_usd).
 */
class HistoryController extends Controller
{
    public function index(): View
    {
        return view('admin.history.index', [
            'sources' => HistoryDataSource::orderBy('name')->get(),
            'reports' => HistoryReport::with('vehicle.make', 'vehicle.vehicleModel', 'requester')
                ->whereIn('status', ['purchased', 'refunded'])->latest('purchased_at')->paginate(20),
        ]);
    }

    public function updateSource(Request $request, HistoryDataSource $source): RedirectResponse
    {
        $validated = $request->validate(['status' => ['required', 'in:live,manual,unavailable']]);

        $source->update(['status' => $validated['status']]);

        AuditLog::record($request->user(), 'history.source.update', $source, [
            'key' => $source->key, 'status' => $validated['status'],
        ]);

        return back()->with('status', "Source “{$source->name}” set to {$validated['status']}.");
    }

    /** Manual dealer-supplied service record into a report's service section. */
    public function addServiceRecord(Request $request, HistoryReport $report): RedirectResponse
    {
        $validated = $request->validate([
            'date'       => ['required', 'date'],
            'note'       => ['required', 'string', 'max:255'],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
        ]);

        $section = $report->sections()->where('type', 'service')->first();
        abort_if($section === null, 404, 'No service section on this report.');

        $records = $section->data['records'] ?? [];
        $records[] = array_filter([
            'date'        => $validated['date'],
            'note'        => $validated['note'],
            'odometer_km' => $validated['odometer_km'] ?? null,
        ], fn ($v) => $v !== null);

        $section->update([
            'data'         => ['records' => $records],
            'availability' => 'manual',
            'provenance'   => 'Dealer-supplied (manual entry)',
            'retrieved_at' => now(),
        ]);

        AuditLog::record($request->user(), 'history.service_record.add', $report, ['date' => $validated['date']]);

        return back()->with('status', 'Service record added.');
    }

    public function refund(Request $request, HistoryReport $report, HistoryPurchaseService $purchase): RedirectResponse
    {
        abort_unless($report->isPurchased(), 422, 'Only purchased reports can be refunded.');

        $purchase->refund($report, $request->user());

        return back()->with('status', 'Report refunded.');
    }
}
