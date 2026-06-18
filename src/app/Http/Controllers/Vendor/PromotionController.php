<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Modules\Promotions\Models\PromotionPackage;
use App\Modules\Promotions\Services\PromotionService;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function __construct(private readonly PromotionService $promotions) {}

    /** Feature, bump, or badge a listing. */
    public function promote(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVendor($request, $vehicle);

        $action = $request->validate(['action' => ['required', 'in:feature,bump,badge']])['action'];
        $vendor = $request->attributes->get('vendor');

        if ($action === 'badge') {
            if (! $this->promotions->vendorIsVerified($vendor)) {
                return back()->withErrors(['promotion' => 'A verified-seller badge requires approved verification documents.']);
            }

            $result = $this->promotions->badge(
                $vehicle,
                returnUrl: route('vendor.vehicles.show', $vehicle),
                resultUrl: route('payments.webhook'),
            );
        } else {
            $result = $action === 'feature'
                ? $this->promotions->feature($vehicle)
                : $this->promotions->bump($vehicle);
        }

        if (! empty($result['redirectUrl'])) {
            return redirect()->away($result['redirectUrl']);
        }

        return back()->with('status', ucfirst($action) . ' applied using a package credit.');
    }

    public function packages(Request $request): View
    {
        $vendor       = $request->attributes->get('vendor');
        $packages     = PromotionPackage::where('is_active', true)->orderBy('price')->get();
        $subscription = $this->promotions->activeSubscription($vendor->id);

        return view('vendor.promotions.packages', compact('packages', 'subscription'));
    }

    public function buyPackage(Request $request, PromotionPackage $package): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');

        $purchase = $this->promotions->buyPackage(
            $vendor,
            $package,
            returnUrl: route('vendor.promotions.packages'),
            resultUrl: route('payments.webhook'),
        );

        if (! empty($purchase->redirect_url)) {
            return redirect()->away($purchase->redirect_url);
        }

        return back()->withErrors(['promotion' => 'Could not start the purchase. Please try again.']);
    }

    private function authorizeVendor(Request $request, Vehicle $vehicle): void
    {
        abort_unless($vehicle->vendor_id === $request->attributes->get('vendor')->id, 403);
    }
}
