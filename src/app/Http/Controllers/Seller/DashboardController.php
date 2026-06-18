<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Modules\Verification\Services\TierService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly TierService $tiers) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        // Real listing stats (no placeholders) scoped to this seller.
        $vehicles = $user->vehicles()->latest()->take(5)->get();

        return view('seller.dashboard', [
            'user'           => $user,
            'recentVehicles' => $vehicles,
            'vehicleCount'   => $user->vehicles()->count(),
            'activeCount'    => $user->vehicles()->where('status', 'active')->count(),
            'pendingCount'   => $user->vehicles()->where('status', 'pending')->count(),
            'vehicleLimit'   => $this->tiers->sellerVehicleLimit($user),
            'remainingSlots' => $this->tiers->sellerRemainingVehicleSlots($user),
        ]);
    }
}
