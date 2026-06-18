<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Modules\Rfq\Models\PartRequest;
use App\Modules\Rfq\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vendor side of RFQ: browse open part requests and submit quotes.
 */
class RfqController extends Controller
{
    public function index(Request $request): View
    {
        $vendorId = $request->attributes->get('vendor')->id;

        $requests = PartRequest::openForQuotes()
            ->with(['make', 'vehicleModel', 'quotes' => fn ($q) => $q->where('vendor_id', $vendorId)])
            ->latest()
            ->paginate(20);

        return view('vendor.rfq.index', compact('requests'));
    }

    public function quote(Request $request, PartRequest $partRequest): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');

        abort_unless($partRequest->isOpenForQuotes(), 422);

        if ($partRequest->quotes()->where('vendor_id', $vendor->id)->exists()) {
            return back()->withErrors(['quote' => 'You have already quoted this request.']);
        }

        $validated = $request->validate([
            'price'             => ['required', 'numeric', 'min:0.01'],
            'condition'         => ['required', 'string', 'max:30'],
            'delivery_estimate' => ['nullable', 'string', 'max:120'],
            'notes'             => ['nullable', 'string', 'max:500'],
        ]);

        Quote::create([
            'part_request_id'   => $partRequest->id,
            'vendor_id'         => $vendor->id,
            'submitted_by'      => $request->user()->id,
            'price'             => $validated['price'],
            'condition'         => $validated['condition'],
            'delivery_estimate' => $validated['delivery_estimate'] ?? null,
            'notes'             => $validated['notes'] ?? null,
            'status'            => 'active',
            'expires_at'        => now()->addDays(7),
        ]);

        // First quote moves an open request into "quoted".
        if ($partRequest->status === 'open') {
            $partRequest->update(['status' => 'quoted']);
        }

        return back()->with('status', 'Quote submitted.');
    }
}
