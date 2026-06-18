<?php

namespace App\Modules\Delivery\Services;

use App\Models\User;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Models\RiderCashSession;

/**
 * The COD cash loop (BUSINESS_MODEL.md §3, Phase 14 §14.2). Cash a rider
 * collects on FBS-COD deliveries accumulates into a daily session. Those orders
 * do NOT complete (and therefore do not settle into the vendor wallet) until the
 * session is reconciled — this structurally guarantees commission capture on
 * FBS-COD before any money is credited.
 */
class CashReconciliationService
{
    public function sessionFor(User $rider, ?string $date = null): RiderCashSession
    {
        return RiderCashSession::firstOrCreate(
            ['rider_id' => $rider->id, 'session_date' => $date ?? now()->toDateString()],
            ['status' => 'open', 'expected_total' => 0],
        );
    }

    /**
     * Attach a delivered COD order to the rider's open session and accrue the
     * expected cash. The order stays "delivered" until reconciliation.
     */
    public function attach(Delivery $delivery): void
    {
        $session = $this->sessionFor($delivery->rider);

        $delivery->update(['cash_session_id' => $session->id]);
        $session->increment('expected_total', (float) $delivery->cod_expected);
    }

    /**
     * Reconcile a session against the cash a rider actually hands in. On an exact
     * match the session's orders complete (and settle). A mismatch is flagged for
     * admin resolution — no order settles until the discrepancy is resolved.
     */
    public function reconcile(RiderCashSession $session, float $collected, User $admin): void
    {
        if (! in_array($session->status, ['open', 'discrepancy'], true)) {
            return;
        }

        $matches = round((float) $session->expected_total, 2) === round($collected, 2);

        $session->update([
            'collected_total' => $collected,
            'status'          => $matches ? 'reconciled' : 'discrepancy',
            'reconciled_by'   => $admin->id,
            'reconciled_at'   => $matches ? now() : null,
        ]);

        if ($matches) {
            $this->completeSessionOrders($session);
        }
    }

    /**
     * Admin override: accept a flagged session and complete its orders anyway.
     */
    public function resolve(RiderCashSession $session, User $admin): void
    {
        $session->update([
            'status'        => 'reconciled',
            'reconciled_by' => $admin->id,
            'reconciled_at' => now(),
        ]);

        $this->completeSessionOrders($session);
    }

    private function completeSessionOrders(RiderCashSession $session): void
    {
        foreach ($session->deliveries()->with('order')->get() as $delivery) {
            $order = $delivery->order;

            if ($order !== null && $order->canTransitionTo('completed')) {
                $order->transitionTo('completed'); // → OrderCompletedEvent → wallet settlement
            }
        }
    }
}
