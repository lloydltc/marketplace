<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Modules\Products\Models\Product;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * H8: public "Find a Dealer" directory + per-dealer storefronts. Only approved
 * vendors are ever exposed; featured placement is paid and config-driven.
 */
class DealerController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q', ''));

        $dealers = Vendor::query()
            ->approved()
            ->when($term !== '', fn ($q) => $q->where('name', 'ilike', '%' . $term . '%'))
            ->withCount([
                'vehicles as live_vehicles_count' => fn ($q) => $this->liveVehicles($q),
                'products as live_products_count' => fn ($q) => $q->where('status', 'active'),
            ])
            ->orderByDesc('featured_until') // featured first, then name
            ->orderBy('name')
            ->paginate((int) config('dealers.per_page', 24))
            ->withQueryString();

        $featured = Vendor::query()
            ->approved()
            ->featured()
            ->withCount([
                'vehicles as live_vehicles_count' => fn ($q) => $this->liveVehicles($q),
                'products as live_products_count' => fn ($q) => $q->where('status', 'active'),
            ])
            ->orderByDesc('featured_until')
            ->limit((int) config('dealers.featured_count', 8))
            ->get();

        return view('dealers.index', compact('dealers', 'featured', 'term'));
    }

    public function show(Vendor $vendor): View
    {
        // Only approved dealers have a public storefront.
        abort_unless($vendor->isApproved(), 404);

        $perSection = (int) config('dealers.storefront_listings', 12);

        $vehicles = Vehicle::query()
            ->where('vendor_id', $vendor->id)
            ->tap(fn ($q) => $this->liveVehicles($q))
            ->with(['make', 'vehicleModel', 'images'])
            ->latest()
            ->paginate($perSection, ['*'], 'vehicles')
            ->withQueryString();

        $products = Product::query()
            ->where('vendor_id', $vendor->id)
            ->where('status', 'active')
            ->with(['category', 'images'])
            ->latest()
            ->paginate($perSection, ['*'], 'products')
            ->withQueryString();

        return view('dealers.show', compact('vendor', 'vehicles', 'products'));
    }

    /** Active + not-yet-expired vehicle constraint (mirrors the public catalogue). */
    private function liveVehicles($query): void
    {
        $query->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
