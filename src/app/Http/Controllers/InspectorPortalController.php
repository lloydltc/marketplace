<?php

namespace App\Http\Controllers;

use App\Modules\Inspection\Models\Inspection;
use App\Modules\Inspection\Models\Inspector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * TI4: portal for a vetted inspector (a user linked to an Inspector record) —
 * assigned jobs + standardized report submission. Manual-first; admins can also
 * operate on their behalf.
 */
class InspectorPortalController extends Controller
{
    public function index(Request $request): View
    {
        $inspector = $this->inspectorFor($request);

        $inspections = $inspector->inspections()
            ->whereIn('status', ['paid', 'in_progress', 'completed'])
            ->with(['buyer', 'vehicle.make', 'vehicle.vehicleModel'])
            ->latest()->paginate(20);

        return view('inspector.index', compact('inspector', 'inspections'));
    }

    public function submitReport(Request $request, Inspection $inspection): RedirectResponse
    {
        $inspector = $this->inspectorFor($request);
        abort_unless($inspection->inspector_id === $inspector->id, 403);
        abort_unless($inspection->isPaid(), 422, 'This inspection is not paid yet.');

        $validated = $request->validate([
            'verdict' => ['required', 'in:' . implode(',', array_keys(config('inspection.verdicts', [])))],
            'items'   => ['required', 'array'],
            'items.*' => ['nullable', 'in:pass,fail,na'],
            'notes'   => ['nullable', 'array'],
        ]);

        $checklist = [];
        foreach (config('inspection.checklist', []) as $item) {
            $checklist[] = [
                'item'   => $item,
                'status' => $validated['items'][$item] ?? 'na',
                'note'   => $validated['notes'][$item] ?? null,
            ];
        }

        $inspection->update([
            'report'              => ['checklist' => $checklist],
            'verdict'             => $validated['verdict'],
            'status'              => 'completed',
            'report_submitted_at' => now(),
        ]);

        // Notify the buyer their report is ready (shared notification architecture).
        $inspection->buyer?->notify(new \App\Notifications\InspectionReportReadyNotification($inspection));

        return redirect()->route('inspector.index')->with('status', 'Report submitted to the buyer.');
    }

    private function inspectorFor(Request $request): Inspector
    {
        $inspector = Inspector::where('user_id', $request->user()->id)->first();
        abort_if($inspector === null, 403, 'You are not a registered inspector.');

        return $inspector;
    }
}
