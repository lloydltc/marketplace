<?php

namespace App\Modules\Delivery\Services;

use App\Models\User;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Orders\Models\Order;
use RuntimeException;

/**
 * Drives the Fulfilled-by-Salma delivery lifecycle. Each rider action advances
 * the delivery and feeds the order state machine (Phase 12). Settlement timing
 * differs by payment method: prepaid FBS completes (and settles) on delivery;
 * FBS-COD waits for cash reconciliation (see CashReconciliationService).
 */
class DeliveryService
{
    public function __construct(private readonly CashReconciliationService $cash) {}

    /**
     * Dispatch: create the delivery (if needed) and assign a rider. Moves the
     * order from processing into the awaiting-pickup state.
     */
    public function assignRider(Order $order, User $rider): Delivery
    {
        if ($order->fulfilment_track !== 'fbs') {
            throw new RuntimeException('Only Fulfilled-by-Salma orders are delivered by riders.');
        }

        $delivery = Delivery::firstOrCreate(
            ['order_id' => $order->id],
            ['status' => 'pending', 'cod_expected' => $order->isCod() ? $order->total : 0],
        );

        $delivery->update([
            'rider_id'    => $rider->id,
            'status'      => 'assigned',
            'assigned_at' => now(),
        ]);

        if ($order->canTransitionTo('awaiting_pickup')) {
            $order->transitionTo('awaiting_pickup');
        }

        return $delivery;
    }

    public function pickUp(Delivery $delivery): void
    {
        $delivery->update(['status' => 'picked_up', 'picked_up_at' => now()]);

        if ($delivery->order->canTransitionTo('out_for_delivery')) {
            $delivery->order->transitionTo('out_for_delivery');
        }
    }

    /**
     * Mark delivered with proof. COD deliveries record the cash and join the
     * rider's cash session (completion deferred); prepaid deliveries complete now.
     */
    public function markDelivered(Delivery $delivery, ?float $codCollected = null, ?string $note = null): void
    {
        $order = $delivery->order;

        $delivery->update([
            'status'        => 'delivered',
            'delivered_at'  => now(),
            'cod_collected' => $delivery->isCod() ? ($codCollected ?? 0) : null,
            'proof_note'    => $note,
        ]);

        if ($order->canTransitionTo('delivered')) {
            $order->transitionTo('delivered');
        }

        if ($delivery->isCod()) {
            // Cash is with the rider — settle only after the end-of-day cash-in.
            $this->cash->attach($delivery->fresh());
        } elseif ($order->canTransitionTo('completed')) {
            // Prepaid: platform already holds the money → complete + settle now.
            $order->transitionTo('completed');
        }
    }
}
