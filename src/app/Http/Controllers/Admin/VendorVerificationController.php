<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Vendor;
use App\Modules\Verification\Models\VendorVerification;
use App\Modules\Verification\Services\TierEvaluator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * VB2: admin decides a single verification dimension for a vendor. Approving sets
 * an expiry (config-driven) for re-verification; the vendor's badge tier is
 * recomputed immediately and every action is audit-logged (R6).
 */
class VendorVerificationController extends Controller
{
    public function __construct(private readonly TierEvaluator $tiers) {}

    public function update(Request $request, Vendor $vendor, string $dimension): RedirectResponse
    {
        abort_unless(in_array($dimension, config('verification.dimensions', []), true), 404);

        $validated = $request->validate([
            'status'       => ['required', 'in:approved,rejected,pending'],
            'notes'        => ['nullable', 'string', 'max:1000'],
            'evidence_ref' => ['nullable', 'uuid'],
        ]);

        $months = (int) config('verification.dimension_expiry_months', 12);
        $approved = $validated['status'] === 'approved';

        $verification = VendorVerification::updateOrCreate(
            ['vendor_id' => $vendor->id, 'dimension' => $dimension],
            [
                'status'       => $validated['status'],
                'notes'        => $validated['notes'] ?? null,
                'evidence_ref' => $validated['evidence_ref'] ?? null,
                'verified_by'  => $request->user()->id,
                'verified_at'  => $approved ? now() : null,
                'expires_at'   => $approved && $months > 0 ? now()->addMonths($months) : null,
            ],
        );

        $before = $vendor->verification_tier;
        $after = $this->tiers->recompute($vendor->fresh());

        AuditLog::record($request->user(), 'verification.dimension.' . $validated['status'], $vendor, [
            'dimension' => $dimension,
            'tier_before' => $before,
            'tier_after' => $after,
        ]);

        return back()->with('status', ucfirst($dimension) . " marked {$validated['status']}." .
            ($before !== $after ? " Badge tier is now " . ($after ? config("verification.tiers.{$after}.label") : 'none') . '.' : ''));
    }
}
