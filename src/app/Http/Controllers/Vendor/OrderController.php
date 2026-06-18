<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Exceptions\IllegalOrderTransitionException;
use App\Modules\Orders\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vendor order management: list, inspect, and advance their orders through the
 * fulfilment state machine. Scoped to the vendor resolved by vendor.scope.
 */
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::forVendor($this->vendorId($request))
            ->with('items')
            ->latest()
            ->paginate(20);

        return view('vendor.orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorizeVendor($request, $order);
        $order->load(['items', 'payments']);

        return view('vendor.orders.show', compact('order'));
    }

    public function transition(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeVendor($request, $order);

        $to = $request->validate(['to' => ['required', 'string']])['to'];

        try {
            $order->transitionTo($to);
        } catch (IllegalOrderTransitionException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'Order updated to ' . str_replace('_', ' ', $to) . '.');
    }

    private function vendorId(Request $request): string
    {
        return $request->attributes->get('vendor')->id;
    }

    private function authorizeVendor(Request $request, Order $order): void
    {
        abort_unless($order->vendor_id === $this->vendorId($request), 403);
    }
}
