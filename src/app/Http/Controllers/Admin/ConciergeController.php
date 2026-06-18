<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Modules\Concierge\Models\ConciergeRequest;
use App\Modules\Concierge\Services\ConciergeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin concierge queue — the whole workflow is driven from here so an ops
 * person can run a request end-to-end with no developer involvement.
 */
class ConciergeController extends Controller
{
    public function __construct(private readonly ConciergeService $concierge) {}

    public function index(): View
    {
        $requests = ConciergeRequest::with('buyer')
            ->whereNotIn('status', ['closed', 'cancelled'])
            ->latest()
            ->paginate(30);

        return view('admin.concierge.index', compact('requests'));
    }

    public function show(ConciergeRequest $conciergeRequest): View
    {
        $conciergeRequest->load(['buyer', 'sourcedVendor', 'make', 'vehicleModel']);
        $vendors = Vendor::where('status', 'approved')->orderBy('name')->get();

        return view('admin.concierge.show', ['request' => $conciergeRequest, 'vendors' => $vendors]);
    }

    public function quote(Request $request, ConciergeRequest $conciergeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'part_value'        => ['required', 'numeric', 'min:0.01'],
            'delivery_fee'      => ['required', 'numeric', 'min:0'],
            'sourced_vendor_id' => ['nullable', 'exists:vendors,id'],
        ]);

        $this->concierge->quote(
            $conciergeRequest,
            (float) $validated['part_value'],
            (float) $validated['delivery_fee'],
            $validated['sourced_vendor_id'] ?? null,
        );

        return back()->with('status', 'Quote sent to the buyer.');
    }

    public function transition(Request $request, ConciergeRequest $conciergeRequest): RedirectResponse
    {
        $to = $request->validate(['to' => ['required', 'string']])['to'];

        if (! $this->concierge->transition($conciergeRequest, $to)) {
            return back()->withErrors(['concierge' => "Cannot move from {$conciergeRequest->status} to {$to}."]);
        }

        return back()->with('status', 'Updated to ' . str_replace('_', ' ', $to) . '.');
    }
}
