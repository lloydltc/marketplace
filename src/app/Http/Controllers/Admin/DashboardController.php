<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use App\Modules\Products\Models\Product;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // R7: real, query-backed KPIs. P5: cached briefly so the admin landing
        // page doesn't re-run platform-wide aggregates on every hit.
        $stats = Cache::remember('dashboard.admin.stats', now()->addSeconds(60), function () {
            $pendingApplications = User::whereIn('role', ['vendor_admin', 'private_seller'])
                ->where('status', 'pending')->count();

            return [
                'total_users'       => User::count(),
                'active_vendors'    => Vendor::approved()->count(),
                'listings'          => Product::active()->count() + Vehicle::active()->count(),
                'pending_approvals' => $pendingApplications
                    + Product::byStatus('pending')->count()
                    + Vehicle::byStatus('pending')->count(),
            ];
        });

        return view('admin.dashboard', compact('stats'));
    }
}
