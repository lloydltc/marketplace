<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Vendor;
use App\Modules\Verification\Services\TierEvaluator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * VB4/VB5: admin badge management — revoke/reinstate a vendor's trust badge and
 * grant/clear a manual tier (e.g. Manufacturer-Authorized). All audited (R6).
 */
class VendorBadgeController extends Controller
{
    public function __construct(private readonly TierEvaluator $tiers) {}

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        $validated = $request->validate([
            'action'      => ['required', 'in:revoke,reinstate,grant,clear_grant'],
            'reason'      => ['nullable', 'string', 'max:500'],
            'manual_tier' => ['nullable', 'in:' . implode(',', array_keys(config('verification.tiers', [])))],
        ]);

        match ($validated['action']) {
            'revoke'      => $vendor->update(['badge_revoked_at' => now(), 'badge_revoked_reason' => $validated['reason'] ?? 'Policy violation']),
            'reinstate'   => $vendor->update(['badge_revoked_at' => null, 'badge_revoked_reason' => null]),
            'grant'       => $vendor->update(['manual_tier' => $validated['manual_tier']]),
            'clear_grant' => $vendor->update(['manual_tier' => null]),
        };

        $tier = $this->tiers->recompute($vendor->fresh());

        AuditLog::record($request->user(), 'badge.' . $validated['action'], $vendor, [
            'reason'      => $validated['reason'] ?? null,
            'manual_tier' => $validated['manual_tier'] ?? null,
            'tier_after'  => $tier,
        ]);

        return back()->with('status', 'Badge updated.');
    }
}
