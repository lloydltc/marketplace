<?php

namespace App\Modules\Concierge\Services;

use App\Models\Vendor;
use App\Modules\Commerce\Services\CommissionCalculator;
use App\Modules\Concierge\Models\ConciergeRequest;
use App\Modules\Payments\Services\PesepayClient;
use App\Modules\Settings\Services\SettingsService;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Support\Str;

/**
 * Concierge workflow (BUSINESS_MODEL.md §6 Tier 2). One ops person drives a
 * request through its states; the platform collects everything via Pesepay and,
 * when the part is sourced on-platform, settles the vendor via the wallet —
 * exactly like an FBS order. Fees are settings-driven (no hardcoded money).
 */
class ConciergeService
{
    /** Admin-driven lifecycle. */
    private const TRANSITIONS = [
        'new'        => ['sourcing', 'cancelled'],
        'sourcing'   => ['quoted', 'cancelled'],
        'quoted'     => ['paid', 'cancelled'],
        'paid'       => ['fulfilling', 'cancelled'],
        'fulfilling' => ['delivered'],
        'delivered'  => ['closed'],
    ];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly PesepayClient $gateway,
        private readonly WalletService $wallet,
        private readonly CommissionCalculator $commission
    ) {}

    /** Service fee = max(flat minimum, percentage of part value). */
    public function feeFor(float $partValue): float
    {
        $minimum = $this->settings->getDecimal('concierge.fee_minimum', 5);
        $percent = $this->settings->getDecimal('concierge.fee_percent', 10);

        return round(max($minimum, $partValue * $percent / 100), 2);
    }

    /**
     * Admin quotes the buyer: set part value, delivery, computed service fee and
     * total, optional on-platform source, and move to "quoted".
     */
    public function quote(ConciergeRequest $request, float $partValue, float $deliveryFee, ?string $sourcedVendorId): void
    {
        $serviceFee = $this->feeFor($partValue);

        $request->update([
            'part_value'        => $partValue,
            'service_fee'       => $serviceFee,
            'delivery_fee'      => $deliveryFee,
            'total'             => round($partValue + $serviceFee + $deliveryFee, 2),
            'sourced_vendor_id' => $sourcedVendorId,
            'status'            => 'quoted',
        ]);
    }

    public function canTransition(ConciergeRequest $request, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$request->status] ?? [], true);
    }

    /**
     * Advance the workflow. Closing a delivered request settles the vendor.
     */
    public function transition(ConciergeRequest $request, string $to): bool
    {
        if (! $this->canTransition($request, $to)) {
            return false;
        }

        $request->update(['status' => $to]);

        if ($to === 'closed') {
            $this->settle($request->fresh());
        }

        return true;
    }

    // ─── Payment ────────────────────────────────────────────────────────────────

    public function initiatePayment(ConciergeRequest $request, string $returnUrl, string $resultUrl): void
    {
        $reference = 'CON-' . strtoupper(Str::random(12));

        $result = $this->gateway->initiateTransaction(
            amount: (float) $request->total,
            currency: $request->currency,
            reason: 'SalmaDrive concierge order',
            merchantReference: $reference,
            returnUrl: $returnUrl,
            resultUrl: $resultUrl,
        );

        $request->update([
            'merchant_reference' => $reference,
            'gateway_ref'        => $result['referenceNumber'] ?: null,
            'redirect_url'       => $result['redirectUrl'],
        ]);
    }

    public function confirmPayment(ConciergeRequest $request, string $transactionStatus, ?string $rawPayload = null): void
    {
        $hash = $rawPayload !== null ? hash('sha256', $rawPayload) : null;

        if ($request->isPaid()) {
            return;
        }
        if ($hash !== null && $request->webhook_payload_hash === $hash) {
            return;
        }

        if (strtoupper($transactionStatus) === 'SUCCESS') {
            $request->update([
                'payment_status'       => 'paid',
                'paid_at'              => now(),
                'webhook_payload_hash' => $hash,
                'status'               => $request->status === 'quoted' ? 'paid' : $request->status,
            ]);
        } else {
            $request->update(['webhook_payload_hash' => $hash]);
        }
    }

    // ─── Settlement (on-platform source → wallet credit, like FBS) ───────────────

    public function settle(ConciergeRequest $request): void
    {
        if ($request->sourced_vendor_id === null || $request->settled_at !== null) {
            return;
        }

        /** @var Vendor $vendor */
        $vendor     = $request->sourcedVendor;
        $commission = $this->commission->forLines($vendor, [
            ['line_total' => (float) $request->part_value, 'category' => null],
        ]);

        $this->wallet->post($this->wallet->walletFor($vendor), 'SALE_CREDIT', $commission['net'], [
            'source_type'     => 'concierge',
            'source_id'       => $request->id,
            'idempotency_key' => 'concierge:' . $request->id,
            'description'     => 'Concierge part settlement',
        ]);

        $request->update(['settled_at' => now()]);
    }
}
