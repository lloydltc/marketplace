<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Modules\Verification\Services\TierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VendorTierController extends Controller
{
    public function __construct(private readonly TierService $tierService) {}

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        $request->validate([
            'tier' => ['required', 'string', 'in:' . implode(',', config('tiers.tiers', ['unverified', 'premium']))],
        ]);

        $this->tierService->upgradeVendorTier($vendor, $request->input('tier'));

        return redirect()
            ->route('admin.vendors.show', $vendor)
            ->with('status', "Vendor tier updated to \"{$request->input('tier')}\".");
    }
}
