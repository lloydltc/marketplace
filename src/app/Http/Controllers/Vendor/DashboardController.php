<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $vendor = $request->attributes->get('vendor');

        // R7: real, vendor-scoped KPIs. P5: cached briefly per vendor.
        $stats = [
            'active_listings' => 0,
            'pending_orders'  => 0,
            'team_members'    => 0,
            'tier'            => $vendor?->tier,
        ];

        if ($vendor !== null) {
            $stats = Cache::remember("dashboard.vendor.stats.{$vendor->id}", now()->addSeconds(60), function () use ($vendor) {
                return [
                    'active_listings' => $vendor->products()->where('status', 'active')->count()
                        + $vendor->vehicles()->where('status', 'active')->count(),
                    // Orders still needing fulfilment action (paid, not yet completed/closed).
                    'pending_orders' => Order::forVendor($vendor->id)
                        ->whereNotIn('status', ['completed', 'cancelled', 'refunded', 'pending_payment', 'failed'])
                        ->count(),
                    'team_members' => $vendor->users()->count(),
                    'tier'         => $vendor->tier,
                ];
            });
        }

        return view('vendor.dashboard', compact('vendor', 'stats'));
    }
}
