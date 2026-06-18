<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Delivery\Models\DeliveryZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryZoneController extends Controller
{
    public function index(): View
    {
        $zones = DeliveryZone::orderBy('name')->get();

        return view('admin.delivery-zones.index', compact('zones'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'flat_fee' => ['required', 'numeric', 'min:0'],
        ]);

        DeliveryZone::create($validated + ['is_active' => true]);

        return back()->with('status', 'Zone added.');
    }

    public function toggle(DeliveryZone $zone): RedirectResponse
    {
        $zone->update(['is_active' => ! $zone->is_active]);

        return back()->with('status', 'Zone updated.');
    }
}
