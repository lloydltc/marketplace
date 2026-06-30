<?php

namespace App\Modules\History\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\History\Models\HistoryReport;
use App\Modules\Payments\Services\PesepayClient;
use Illuminate\Support\Str;

/**
 * HR3/HR4: report purchase via the existing Pesepay gateway. A zero-priced report
 * (config) is granted instantly; otherwise we initiate a gateway transaction and
 * confirm it on return. Purchases + refunds are audited.
 */
class HistoryPurchaseService
{
    public function __construct(private readonly PesepayClient $gateway) {}

    /** Begin a purchase. Returns a gateway redirect URL, or null when granted free. */
    public function initiate(HistoryReport $report, string $returnUrl, string $resultUrl): ?string
    {
        if ($report->isPurchased()) {
            return null;
        }

        if ($report->price_minor <= 0) {
            $this->markPurchased($report, 'free');

            return null;
        }

        $reference = 'HR-' . Str::upper(Str::random(12));
        $resp = $this->gateway->initiateTransaction(
            $report->priceUsd(), $report->currency, 'Vehicle history report', $reference, $returnUrl, $resultUrl,
        );

        $report->update(['payment_reference' => $resp['referenceNumber'] ?: $reference, 'status' => 'ready']);

        return $resp['redirectUrl'];
    }

    /** Confirm a pending purchase by polling the gateway. Returns true if now purchased. */
    public function confirm(HistoryReport $report): bool
    {
        if ($report->isPurchased()) {
            return true;
        }
        if (empty($report->payment_reference)) {
            return false;
        }

        $status = $this->gateway->checkStatus($report->payment_reference);
        $paid = ($status['paid'] ?? false) === true
            || strtoupper((string) ($status['transactionStatus'] ?? '')) === 'SUCCESS';

        if ($paid) {
            $this->markPurchased($report, $report->payment_reference);
        }

        return $paid;
    }

    public function markPurchased(HistoryReport $report, string $reference): void
    {
        $report->update([
            'status'            => 'purchased',
            'payment_reference' => $reference,
            'purchased_at'      => now(),
        ]);

        AuditLog::record($report->requester, 'history.report.purchased', $report, [
            'vehicle_id' => $report->vehicle_id, 'price_minor' => $report->price_minor,
        ]);
    }

    public function refund(HistoryReport $report, ?User $actor): void
    {
        $report->update(['status' => 'refunded', 'refunded_at' => now()]);

        AuditLog::record($actor, 'history.report.refunded', $report, ['vehicle_id' => $report->vehicle_id]);
    }
}
