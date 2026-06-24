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

        // H9: listings needing renewal — expired + expiring soon (settings window).
        $soonDays = app(\App\Modules\Settings\Services\SettingsService::class)->getInt('listings.expiry_soon_days', 7);
        $attention = $user->vehicles()
            ->where(fn ($q) => $q->where('status', 'expired')->orWhere(fn ($w) => $w->expiringWithin($soonDays)))
            ->with(['make', 'vehicleModel'])
            ->orderByRaw('expires_at IS NULL, expires_at')
            ->get();

        return view('seller.dashboard', [
            'user'             => $user,
            'recentVehicles'   => $vehicles,
            'vehicleCount'     => $user->vehicles()->count(),
            'activeCount'      => $user->vehicles()->where('status', 'active')->count(),
            'pendingCount'     => $user->vehicles()->where('status', 'pending')->count(),
            'vehicleLimit'     => $this->tiers->sellerVehicleLimit($user),
            'remainingSlots'   => $this->tiers->sellerRemainingVehicleSlots($user),
            'attentionVehicles' => $attention,
        ]);
    }
}
