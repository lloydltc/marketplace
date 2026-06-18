<?php

namespace App\Modules\Payments\Services;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Models\Payment;
use Illuminate\Support\Str;

/**
 * Orchestrates gateway payments for prepaid orders. All money lands in the
 * platform account (collect-then-distribute); vendor settlement happens later
 * via the wallet payout cycle (Phase 13). No split payments at the gateway.
 */
class PaymentService
{
    public function __construct(private readonly PesepayClient $gateway) {}

    /**
     * Create a pending payment for an order and initiate the gateway transaction.
     */
    public function initiate(Order $order, string $returnUrl, string $resultUrl): Payment
    {
        $reference = 'PAY-' . strtoupper(Str::random(12));

        $payment = Payment::create([
            'order_id'           => $order->id,
            'gateway'            => 'pesepay',
            'merchant_reference' => $reference,
            'amount'             => $order->total,
            'currency'           => $order->currency,
            'status'             => 'pending',
        ]);

        $result = $this->gateway->initiateTransaction(
            amount: (float) $order->total,
            currency: $order->currency,
            reason: 'SalmaDrive order ' . $order->order_number,
            merchantReference: $reference,
            returnUrl: $returnUrl,
            resultUrl: $resultUrl,
        );

        $payment->update([
            'gateway_ref'  => $result['referenceNumber'] ?: null,
            'redirect_url' => $result['redirectUrl'],
            'poll_url'     => $result['pollUrl'],
        ]);

        return $payment;
    }

    /**
     * Seamless EcoCash / InnBucks payment. The buyer approves on their phone;
     * we record a pending payment and await the webhook (or a status re-check).
     *
     * @param  'ecocash'|'innbucks'  $method
     */
    public function initiateSeamless(
        Order $order,
        string $method,
        ?string $phone,
        string $returnUrl,
        string $resultUrl
    ): Payment {
        $code = (string) config("pesepay.methods.{$method}");

        // EcoCash needs the customer's mobile number; InnBucks needs no field.
        $requiredFields = $method === 'ecocash' && $phone
            ? ['customerPhoneNumber' => $phone]
            : [];

        $reference = 'PAY-' . strtoupper(Str::random(12));

        $payment = Payment::create([
            'order_id'           => $order->id,
            'gateway'            => 'pesepay',
            'merchant_reference' => $reference,
            'method'             => $method,
            'amount'             => $order->total,
            'currency'           => $order->currency,
            'status'             => 'pending',
        ]);

        $result = $this->gateway->makeSeamlessPayment(
            amount: (float) $order->total,
            currency: $order->currency,
            reason: 'SalmaDrive order ' . $order->order_number,
            merchantReference: $reference,
            paymentMethodCode: $code,
            customer: [
                'email'       => $order->buyer_email,
                'phoneNumber' => $phone ?: $order->buyer_phone,
                'name'        => $order->buyer_name,
            ],
            requiredFields: $requiredFields,
            returnUrl: $returnUrl,
            resultUrl: $resultUrl,
        );

        $payment->update([
            'gateway_ref' => $result['referenceNumber'] ?: null,
            'poll_url'    => $result['pollUrl'],
        ]);

        return $payment;
    }

    /**
     * Apply a gateway-reported status to a payment, idempotently.
     *
     * Replayed webhooks (same payload) and any update to an already-terminal
     * payment are no-ops — no status flips back, no money moves twice.
     */
    public function applyGatewayStatus(Payment $payment, string $transactionStatus, ?string $rawPayload = null): void
    {
        $hash = $rawPayload !== null ? hash('sha256', $rawPayload) : null;

        if ($payment->isTerminal()) {
            return;
        }

        if ($hash !== null && $payment->webhook_payload_hash === $hash) {
            return;
        }

        $status = $this->mapStatus($transactionStatus);

        $payment->update([
            'status'               => $status,
            'webhook_payload_hash' => $hash ?? $payment->webhook_payload_hash,
            'paid_at'              => $status === 'paid' ? now() : null,
        ]);

        if ($status === 'paid') {
            $payment->order->markPaid();
        } elseif ($status === 'failed') {
            $payment->order->markFailed();
        }
    }

    /**
     * Flag the order's paid gateway payment as refunded. The actual Pesepay
     * refund is an operational/gateway step; this records that money is owed back.
     */
    public function markRefunded(Order $order): void
    {
        $order->payments()
            ->where('status', 'paid')
            ->get()
            ->each(fn (Payment $p) => $p->update(['status' => 'refunded']));
    }

    private function mapStatus(string $transactionStatus): string
    {
        return match (strtoupper($transactionStatus)) {
            'SUCCESS', 'PAID'                                                          => 'paid',
            'FAILED', 'ERROR', 'CANCELLED', 'DECLINED', 'AUTHORIZATION_FAILED',
            'TIME_OUT', 'CLOSED', 'CLOSED_PERIOD_ELAPSED', 'TERMINATED', 'INSUFFICIENT_FUNDS' => 'failed',
            default                                                                    => 'pending',
        };
    }
}
