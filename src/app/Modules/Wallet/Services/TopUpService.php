<?php

namespace App\Modules\Wallet\Services;

use App\Models\Vendor;
use App\Modules\Payments\Services\PesepayClient;
use App\Modules\Wallet\Models\WalletTopUp;
use Illuminate\Support\Str;

/**
 * Vendor wallet top-ups via Pesepay. A successful gateway result posts a TOP_UP
 * ledger entry (which can lift a vendor back above the floor and reinstate COD).
 */
class TopUpService
{
    public function __construct(
        private readonly PesepayClient $gateway,
        private readonly WalletService $wallet
    ) {}

    public function initiate(Vendor $vendor, float $amount, string $returnUrl, string $resultUrl): WalletTopUp
    {
        $reference = 'TOP-' . strtoupper(Str::random(12));

        $topUp = WalletTopUp::create([
            'vendor_id'          => $vendor->id,
            'merchant_reference' => $reference,
            'amount'             => $amount,
            'currency'           => 'ZWL',
            'status'             => 'pending',
        ]);

        $result = $this->gateway->initiateTransaction(
            amount: $amount,
            currency: 'ZWL',
            reason: 'SalmaDrive wallet top-up',
            merchantReference: $reference,
            returnUrl: $returnUrl,
            resultUrl: $resultUrl,
        );

        $topUp->update([
            'gateway_ref'  => $result['referenceNumber'] ?: null,
            'redirect_url' => $result['redirectUrl'],
        ]);

        return $topUp;
    }

    /**
     * Apply a gateway status to a top-up, idempotently. On success it posts the
     * TOP_UP entry (keyed on the top-up id, so replays never double-credit).
     */
    public function confirm(WalletTopUp $topUp, string $transactionStatus, ?string $rawPayload = null): void
    {
        $hash = $rawPayload !== null ? hash('sha256', $rawPayload) : null;

        if (in_array($topUp->status, ['paid', 'failed'], true)) {
            return;
        }
        if ($hash !== null && $topUp->webhook_payload_hash === $hash) {
            return;
        }

        $success = strtoupper($transactionStatus) === 'SUCCESS';

        if (! $success) {
            $topUp->update(['status' => 'failed', 'webhook_payload_hash' => $hash]);

            return;
        }

        $topUp->update(['status' => 'paid', 'paid_at' => now(), 'webhook_payload_hash' => $hash]);

        $this->wallet->post($this->wallet->walletFor($topUp->vendor), 'TOP_UP', (float) $topUp->amount, [
            'source_type'     => 'wallet_top_up',
            'source_id'       => $topUp->id,
            'idempotency_key' => 'topup:' . $topUp->id,
            'description'     => 'Wallet top-up',
        ]);
    }
}
