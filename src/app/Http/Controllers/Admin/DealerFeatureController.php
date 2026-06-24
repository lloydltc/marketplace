<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * H8: admin sets / clears a dealer's paid featured placement. Audited (R6).
 */
class DealerFeatureController extends Controller
{
    public function store(Request $request, Vendor $vendor): RedirectResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        // Only approved dealers can be promoted to the public carousel.
        abort_unless($vendor->isApproved(), 422, 'Only approved dealers can be featured.');

        $until = now()->addDays($validated['days']);
        $vendor->update(['featured_until' => $until]);

        AuditLog::record($request->user(), 'dealer.featured', $vendor, [
            'days'           => $validated['days'],
            'featured_until' => $until->toIso8601String(),
        ]);

        return back()->with('status', "Dealer featured until {$until->toFormattedDateString()}.");
    }

    public function destroy(Request $request, Vendor $vendor): RedirectResponse
    {
        $vendor->update(['featured_until' => null]);

        AuditLog::record($request->user(), 'dealer.unfeatured', $vendor);

        return back()->with('status', 'Dealer featured placement removed.');
    }
}
