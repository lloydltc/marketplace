<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Services\DeliveryService;
use App\Modules\Orders\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manual dispatch (MVP): assign riders to FBS orders ready for delivery.
 */
class DispatchController extends Controller
{
    public function __construct(private readonly DeliveryService $deliveries) {}

    public function index(): View
    {
        $awaitingDispatch = Order::with('vendor')
            ->where('fulfilment_track', 'fbs')
            ->where('status', 'processing')
            ->doesntHave('delivery')
            ->latest()
            ->get();

        $active = Delivery::with(['order', 'rider'])
            ->whereIn('status', ['assigned', 'picked_up', 'out_for_delivery'])
            ->latest()
            ->get();

        $riders = User::where('role', 'rider')->orderBy('name')->get();

        return view('admin.dispatch.index', compact('awaitingDispatch', 'active', 'riders'));
    }

    public function assign(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate(['rider_id' => ['required', 'exists:users,id']]);

        $rider = User::findOrFail($validated['rider_id']);
        abort_unless($rider->role === 'rider', 422);

        $this->deliveries->assignRider($order, $rider);

        return back()->with('status', "Assigned to {$rider->name}.");
    }
}
