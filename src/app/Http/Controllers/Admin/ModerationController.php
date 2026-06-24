<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ListingReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * H11: admin moderation queue. Lists open reports (user + auto) and lets an admin
 * dismiss them or take the listing down. Every action is audited (R6).
 */
class ModerationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status', 'open');

        $reports = ListingReport::query()
            ->when(in_array($status, ['open', 'actioned', 'dismissed'], true), fn ($q) => $q->where('status', $status))
            ->with(['reportable', 'reporter'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $openCount = ListingReport::open()->count();

        return view('admin.moderation.index', compact('reports', 'status', 'openCount'));
    }

    public function resolve(Request $request, ListingReport $report): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:dismiss,takedown'],
            'note'   => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless($report->isOpen(), 422, 'This report is already resolved.');

        $listing = $report->reportable;

        if ($validated['action'] === 'takedown') {
            // Pull the listing from public view and resolve every open report on it.
            if ($listing !== null) {
                $listing->update(['status' => 'inactive']);

                $listing->reports()->open()->update([
                    'status'          => 'actioned',
                    'resolved_by'     => $request->user()->id,
                    'resolved_at'     => now(),
                    'resolution_note' => $validated['note'] ?? null,
                ]);
            }

            AuditLog::record($request->user(), 'moderation.takedown', $listing, [
                'report_id' => $report->id,
                'reason'    => $report->reason,
            ]);

            return back()->with('status', 'Listing taken down and reports resolved.');
        }

        // Dismiss just this report.
        $report->update([
            'status'          => 'dismissed',
            'resolved_by'     => $request->user()->id,
            'resolved_at'     => now(),
            'resolution_note' => $validated['note'] ?? null,
        ]);

        AuditLog::record($request->user(), 'moderation.dismiss', $listing, [
            'report_id' => $report->id,
            'reason'    => $report->reason,
        ]);

        return back()->with('status', 'Report dismissed.');
    }
}
