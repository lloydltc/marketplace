<?php

namespace App\Modules\Wallet\Services;

use App\Modules\Orders\Models\Order;

/**
 * Turns a completed order into wallet movements (BUSINESS_MODEL.md §3, §4):
 *
 *  - Platform-collected order (FBS any payment, or prepaid VF): the platform
 *    holds the money → SALE_CREDIT of the net proceeds (goods − commission;
 *    delivery margin is retained by the platform).
 *  - VF COD order: the vendor took cash directly → the platform is owed the
 *    commission → COMMISSION_DEBIT.
 *
 * Idempotent via a per-order key, so a duplicated completion event moves nothing twice.
 */
class SettlementService
{
    public function __construct(private readonly WalletService $wallet) {}

    public function settle(Order $order): void
    {
        if ($order->vendor_id === null) {
            return; // private-seller orders have no vendor wallet
        }

        $wallet = $this->wallet->walletFor($order->vendor);
        $key    = 'settlement:' . $order->id;

        if ($this->isVendorCod($order)) {
            $this->wallet->post($wallet, 'COMMISSION_DEBIT', (float) $order->commission_amount, [
                'source_type'     => 'order',
                'source_id'       => $order->id,
                'idempotency_key' => $key,
                'description'     => 'Commission on VF-COD order ' . $order->order_number,
            ]);

            return;
        }

        $this->wallet->post($wallet, 'SALE_CREDIT', (float) $order->net_to_vendor, [
            'source_type'     => 'order',
            'source_id'       => $order->id,
            'idempotency_key' => $key,
            'description'     => 'Net proceeds for order ' . $order->order_number,
        ]);
    }

    /**
     * Reverse a settled order's movement on refund (mirrors the original).
     */
    public function reverse(Order $order): void
    {
        if ($order->vendor_id === null) {
            return;
        }

        $wallet = $this->wallet->walletFor($order->vendor);
        $key    = 'refund:' . $order->id;

        if ($this->isVendorCod($order)) {
            // Original was a debit → credit it back.
            $this->wallet->post($wallet, 'REFUND_ADJUSTMENT', (float) $order->commission_amount, [
                'direction'       => 'credit',
                'source_type'     => 'order',
                'source_id'       => $order->id,
                'idempotency_key' => $key,
                'description'     => 'Refund reversal for ' . $order->order_number,
            ]);

            return;
        }

        // Original was a credit → debit it back.
        $this->wallet->post($wallet, 'REFUND_ADJUSTMENT', (float) $order->net_to_vendor, [
            'direction'       => 'debit',
            'source_type'     => 'order',
            'source_id'       => $order->id,
            'idempotency_key' => $key,
            'description'     => 'Refund reversal for ' . $order->order_number,
        ]);
    }

    private function isVendorCod(Order $order): bool
    {
        return $order->fulfilment_track === 'vendor' && $order->payment_method === 'cod';
    }
}
