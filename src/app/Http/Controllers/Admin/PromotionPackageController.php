<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Promotions\Models\PromotionPackage;
use App\Modules\Promotions\Models\PromotionPurchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionPackageController extends Controller
{
    public function index(): View
    {
        $packages = PromotionPackage::orderBy('price')->get();

        // Promotion revenue summary (completed gateway purchases) for reporting.
        $revenue = PromotionPurchase::where('status', 'completed')
            ->where('funded_by', 'gateway')
            ->sum('amount');

        return view('admin.promotions.index', compact('packages', 'revenue'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:120'],
            'price'           => ['required', 'numeric', 'min:0'],
            'listing_credits' => ['required', 'integer', 'min:0'],
            'feature_credits' => ['required', 'integer', 'min:0'],
            'bump_credits'    => ['required', 'integer', 'min:0'],
            'duration_days'   => ['required', 'integer', 'min:1'],
        ]);

        PromotionPackage::create($validated + ['is_active' => true]);

        return back()->with('status', 'Package created.');
    }

    public function toggle(PromotionPackage $package): RedirectResponse
    {
        $package->update(['is_active' => ! $package->is_active]);

        return back()->with('status', 'Package updated.');
    }
}
