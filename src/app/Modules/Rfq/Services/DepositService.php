<?php

namespace App\Modules\Rfq\Services;

use App\Modules\Payments\Services\PesepayClient;
use App\Modules\Rfq\Models\PartRequest;
use App\Modules\Rfq\Models\RfqDeposit;
use Illuminate\Support\Str;

/**
 * Refundable RFQ commitment deposit (BUSINESS_MODEL.md §6). Paid via Pesepay,
 * recorded immutably through its lifecycle: paid → credited (against the
 * converted order) | refunded (buyer walks away cleanly) | forfeited
 * (abandoned after accepting). Webhook confirmation is idempotent.
 */
class DepositService
{
    public function __construct(
        private readonly PesepayClient $gateway,
        private readonly RfqThresholdService $thresholds
    ) {}

    public function initiate(PartRequest $request, string $returnUrl, string $resultUrl): RfqDeposit
    {
        $reference = 'DEP-' . strtoupper(Str::random(12));
        $amount    = $this->thresholds->depositAmount((float) $request->estimated_value);

        $deposit = RfqDeposit::create([
            'part_request_id'    => $request->id,
            'buyer_user_id'      => $request->buyer_user_id,
            'amount'             => $amount,
            'currency'           => 'ZWL',
            'merchant_reference' => $reference,
            'status'             => 'pending',
        ]);

        $result = $this->gateway->initiateTransaction(
            amount: $amount,
            currency: 'ZWL',
            reason: 'SalmaDrive RFQ commitment deposit',
            merchantReference: $reference,
            returnUrl: $returnUrl,
            resultUrl: $resultUrl,
        );

        $deposit->update([
            'gateway_ref'  => $result['referenceNumber'] ?: null,
            'redirect_url' => $result['redirectUrl'],
        ]);

        return $deposit;
    }

    public function confirm(RfqDeposit $deposit, string $transactionStatus, ?string $rawPayload = null): void
    {
        $hash = $rawPayload !== null ? hash('sha256', $rawPayload) : null;

        if ($deposit->isPaid() || $deposit->isTerminal()) {
            return;
        }
        if ($hash !== null && $deposit->webhook_payload_hash === $hash) {
            return;
        }

        if (strtoupper($transactionStatus) === 'SUCCESS') {
            $deposit->update(['status' => 'paid', 'paid_at' => now(), 'webhook_payload_hash' => $hash]);
        } else {
            $deposit->update(['status' => 'failed', 'webhook_payload_hash' => $hash]);
        }
    }

    /** Credit a paid deposit against the converted order. */
    public function credit(RfqDeposit $deposit): void
    {
        if ($deposit->isPaid()) {
            $deposit->update(['status' => 'credited']);
        }
    }

    /** Refund a paid deposit (buyer closed the request before converting). */
    public function refund(RfqDeposit $deposit): void
    {
        if ($deposit->isPaid()) {
            $deposit->update(['status' => 'refunded']);
        }
    }

    /** Forfeit a paid deposit (abandoned after accepting a quote). */
    public function forfeit(RfqDeposit $deposit): void
    {
        if ($deposit->isPaid()) {
            $deposit->update(['status' => 'forfeited']);
        }
    }
}
