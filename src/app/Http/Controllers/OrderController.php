<?php

namespace App\Http\Controllers;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Services\PaymentService;
use App\Modules\Products\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Buyer-facing order history. Guests place orders via session (Phase 11); these
 * views are for signed-in buyers reviewing their order history.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly InventoryService $inventory
    ) {}

    public function index(Request $request): View
    {
        $orders = Order::forBuyer($request->user()->id)
            ->with('items')
            ->latest()
            ->paginate(20);

        return view('orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorizeBuyer($request, $order);
        $order->load(['items', 'vendor', 'payments']);

        return view('orders.show', compact('order'));
    }

    public function invoice(Request $request, Order $order): View
    {
        $this->authorizeBuyer($request, $order);
        $order->load(['items', 'vendor']);

        return view('orders.invoice', compact('order'));
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeBuyer($request, $order);

        if (! $order->canTransitionTo('cancelled')) {
            return back()->withErrors(['order' => 'This order can no longer be cancelled.']);
        }

        $wasPaid = $order->isPaid();
        $order->transitionTo('cancelled', 'Cancelled by customer');

        // PM10: return held stock for canonical-parts offerings.
        $this->releaseReservedStock($order);

        // A paid prepaid order is owed a refund (tied to the Phase 11 gateway).
        if ($wasPaid) {
            $this->payments->markRefunded($order);
        }

        return back()->with('status', 'Order cancelled.' . ($wasPaid ? ' A refund will be processed.' : ''));
    }

    /**
     * Buyer confirms receipt of a vendor-fulfilled order → completes + settles.
     */
    public function confirmReceipt(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeBuyer($request, $order);

        if (! $order->canTransitionTo('completed')) {
            return back()->withErrors(['order' => 'This order cannot be confirmed yet.']);
        }

        $order->transitionTo('completed');

        return back()->with('status', 'Thanks for confirming — your order is complete.');
    }

    private function authorizeBuyer(Request $request, Order $order): void
    {
        abort_unless($order->buyer_user_id !== null && $order->buyer_user_id === $request->user()->id, 403);
    }

    /** PM10: return reserved stock to canonical-parts offerings on a cancelled order. */
    private function releaseReservedStock(Order $order): void
    {
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            if ($item->product && $item->product->part_id !== null) {
                $this->inventory->release($item->product, $item->quantity, 'order-cancel:' . $order->id);
            }
        }
    }
}

