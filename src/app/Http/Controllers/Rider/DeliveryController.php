<?php

namespace App\Http\Controllers\Rider;

use App\Http\Controllers\Controller;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Services\CashReconciliationService;
use App\Modules\Delivery\Services\DeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function __construct(private readonly DeliveryService $deliveries) {}

    public function index(Request $request): View
    {
        $rider = $request->user();

        $deliveries = Delivery::with('order')
            ->where('rider_id', $rider->id)
            ->whereIn('status', ['assigned', 'picked_up', 'out_for_delivery'])
            ->latest()
            ->get();

        // Today's cash position (COD collected, pending cash-in).
        $session = app(CashReconciliationService::class)->sessionFor($rider);

        return view('rider.deliveries.index', compact('deliveries', 'session'));
    }

    public function pickUp(Request $request, Delivery $delivery): RedirectResponse
    {
        $this->authorizeRider($request, $delivery);
        $this->deliveries->pickUp($delivery);

        return back()->with('status', 'Marked as picked up.');
    }

    public function deliver(Request $request, Delivery $delivery): RedirectResponse
    {
        $this->authorizeRider($request, $delivery);

        $validated = $request->validate([
            'cod_collected' => ['nullable', 'numeric', 'min:0'],
            'proof_note'    => ['nullable', 'string', 'max:500'],
        ]);

        $this->deliveries->markDelivered(
            $delivery,
            isset($validated['cod_collected']) ? (float) $validated['cod_collected'] : null,
            $validated['proof_note'] ?? null,
        );

        return back()->with('status', 'Delivery recorded.');
    }

    private function authorizeRider(Request $request, Delivery $delivery): void
    {
        abort_unless($delivery->rider_id === $request->user()->id, 403);
    }
}
