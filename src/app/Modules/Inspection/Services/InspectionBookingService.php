<?php

namespace App\Modules\Inspection\Services;

use App\Models\User;
use App\Modules\Inspection\Models\Inspection;
use App\Modules\Inspection\Models\Inspector;
use App\Modules\Payments\Services\PesepayClient;
use App\Modules\Settings\Services\SettingsService;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * TI3: inspection booking + payment via the existing Pesepay gateway (mirrors the
 * concierge inline-payment pattern). A zero fee is granted instantly; otherwise a
 * gateway transaction is initiated and confirmed on return.
 */
class InspectionBookingService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly PesepayClient $gateway,
    ) {}

    public function feeMinor(): int
    {
        return (int) round($this->settings->getDecimal('inspection.fee_usd', (float) config('inspection.fee_usd', 30)) * 100);
    }

    public function book(User $buyer, Inspector $inspector, ?Vehicle $vehicle, ?string $vehicleRef, ?Carbon $slot): Inspection
    {
        return Inspection::create([
            'buyer_id'      => $buyer->id,
            'inspector_id'  => $inspector->id,
            'vehicle_id'    => $vehicle?->id,
            'vehicle_ref'   => $vehicle ? null : $vehicleRef,
            'scheduled_for' => $slot,
            'status'        => 'requested',
            'price_minor'   => $this->feeMinor(),
            'currency'      => 'USD',
        ]);
    }

    /** Returns a gateway redirect URL, or null when granted free. */
    public function pay(Inspection $inspection, string $returnUrl, string $resultUrl): ?string
    {
        if ($inspection->isPaid()) {
            return null;
        }
        if ($inspection->price_minor <= 0) {
            $this->markPaid($inspection, 'free');

            return null;
        }

        $reference = 'INSP-' . Str::upper(Str::random(12));
        $resp = $this->gateway->initiateTransaction(
            $inspection->priceUsd(), $inspection->currency, 'Vehicle inspection', $reference, $returnUrl, $resultUrl,
        );
        $inspection->update(['payment_reference' => $resp['referenceNumber'] ?: $reference]);

        return $resp['redirectUrl'];
    }

    public function confirm(Inspection $inspection): bool
    {
        if ($inspection->isPaid()) {
            return true;
        }
        if (empty($inspection->payment_reference)) {
            return false;
        }
        $status = $this->gateway->checkStatus($inspection->payment_reference);
        $paid = ($status['paid'] ?? false) === true || strtoupper((string) ($status['transactionStatus'] ?? '')) === 'SUCCESS';
        if ($paid) {
            $this->markPaid($inspection, $inspection->payment_reference);
        }

        return $paid;
    }

    public function markPaid(Inspection $inspection, string $reference): void
    {
        $inspection->update(['status' => 'paid', 'paid_at' => now(), 'payment_reference' => $reference]);
    }

    public function cancel(Inspection $inspection): void
    {
        $inspection->update(['status' => 'cancelled']);
    }
}
