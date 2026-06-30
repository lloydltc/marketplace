<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Modules\Verification\Services\TierEvaluator;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * VB5: vendor-facing "verification progress" — current badge, per-dimension
 * status, and what's still needed for the next tier.
 */
class VerificationProgressController extends Controller
{
    public function show(Request $request, TierEvaluator $tiers): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        return view('vendor.verification.show', [
            'vendor'   => $vendor,
            'progress' => $tiers->progress($vendor),
        ]);
    }
}
