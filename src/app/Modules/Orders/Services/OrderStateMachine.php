<?php

namespace App\Modules\Orders\Services;

use App\Modules\Orders\Models\Order;

/**
 * The order lifecycle, covering both fulfilment tracks (BUSINESS_MODEL.md §3):
 *
 *   Common:  pending_payment → paid           (prepaid, via gateway)
 *            cod_pending                       (COD, awaiting collection)
 *            paid / cod_pending → processing
 *   FBS:     processing → awaiting_pickup → out_for_delivery → delivered → completed
 *   VF:      processing → vendor_shipping → delivered → completed
 *
 * paid/failed are reached through the payment flow (see Order::markPaid/markFailed);
 * this machine governs fulfilment + cancellation. Illegal moves are rejected.
 */
class OrderStateMachine
{
    public const TERMINAL = ['completed', 'cancelled', 'refunded', 'failed'];

    /**
     * @return string[]
     */
    public function allowedTransitions(Order $order): array
    {
        $shipState = $order->fulfilment_track === 'fbs' ? 'awaiting_pickup' : 'vendor_shipping';

        return match ($order->status) {
            'pending_payment'  => ['cancelled'],
            'cod_pending'      => ['processing', 'cancelled'],
            'paid'             => ['processing', 'cancelled'],
            'processing'       => [$shipState, 'cancelled'],
            'awaiting_pickup'  => ['out_for_delivery', 'cancelled'],
            'out_for_delivery' => ['delivered'],
            'vendor_shipping'  => ['delivered'],
            'delivered'        => ['completed'],
            default            => [], // terminal
        };
    }

    public function canTransition(Order $order, string $to): bool
    {
        return in_array($to, $this->allowedTransitions($order), true);
    }

    public function isTerminal(Order $order): bool
    {
        return in_array($order->status, self::TERMINAL, true);
    }
}
