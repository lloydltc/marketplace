<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Modules\Products\Repositories\ProductRepositoryInterface;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Repositories\VehicleRepositoryInterface;
use App\Support\RecentlyViewed;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly VehicleRepositoryInterface $vehicles
    ) {}

    public function index(RecentlyViewed $recentlyViewedTracker): View
    {
        // Public marketplace landing — visible to guests before they authenticate.
        // Ranking (FBS boost / featured priority) is applied inside the repositories.
        $products = $this->products->paginatePublic([], 8)->items();
        $vehicles = $this->vehicles->paginatePublic([], 8)->items();

        // H6: discovery rails — browse by type / make, all count-driven.
        $typeCounts   = $this->vehicles->countByType();
        $popularMakes = $this->vehicles->popularMakes(10);

        // H7: engagement rails — sponsored (paid placement) + recently viewed.
        $sponsored = $this->vehicles->sponsored((int) config('engagement.sponsored.count', 4));
        $recentlyViewed = $this->hydrateRecentlyViewed($recentlyViewedTracker->ids());

        // H8: featured-dealer carousel (paid placement, config-driven count).
        $featuredDealers = Vendor::query()
            ->approved()->featured()
            ->withCount([
                'vehicles as live_vehicles_count' => fn ($q) => $q->where('status', 'active')
                    ->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now())),
                'products as live_products_count' => fn ($q) => $q->where('status', 'active'),
            ])
            ->orderByDesc('featured_until')
            ->limit((int) config('dealers.featured_count', 8))
            ->get();

        return view('customer.dashboard', compact(
            'products', 'vehicles', 'typeCounts', 'popularMakes', 'sponsored', 'recentlyViewed', 'featuredDealers'
        ));
    }

    /**
     * @param  array<int, string>  $ids
     * @return \Illuminate\Support\Collection<int, Vehicle>
     */
    private function hydrateRecentlyViewed(array $ids): \Illuminate\Support\Collection
    {
        if ($ids === []) {
            return collect();
        }

        return Vehicle::query()
            ->active()
            ->whereIn('id', $ids)
            ->with(['make', 'vehicleModel', 'images'])
            ->get()
            ->sortBy(fn ($v) => array_search($v->id, $ids, true))
            ->values();
    }
}
