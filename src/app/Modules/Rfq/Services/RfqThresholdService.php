<?php

namespace App\Modules\Rfq\Services;

use App\Models\User;
use App\Modules\Rfq\Models\PartRequest;
use App\Modules\Settings\Services\SettingsService;

/**
 * Fair-use thresholds for public RFQ (BUSINESS_MODEL.md §6). Everything here is
 * a no-op when `rfq.thresholds_enabled` is off — the launch configuration — so
 * the feature ships frictionless and the controls are switched on later.
 */
class RfqThresholdService
{
    public function __construct(private readonly SettingsService $settings) {}

    public function enabled(): bool
    {
        return $this->settings->getBool('rfq.thresholds_enabled');
    }

    public function monthlyCount(User $buyer): int
    {
        return PartRequest::where('buyer_user_id', $buyer->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    /**
     * True if the buyer may post another free request. Always true when disabled.
     */
    public function withinFreeQuota(User $buyer): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        return $this->monthlyCount($buyer) < $this->settings->getInt('rfq.free_quota_monthly', 3);
    }

    /**
     * Whether a request of this estimated value needs a commitment deposit.
     * Always false when disabled or when no value is given.
     */
    public function requiresDeposit(?float $estimatedValue): bool
    {
        if (! $this->enabled() || $estimatedValue === null) {
            return false;
        }

        return $estimatedValue >= $this->settings->getDecimal('rfq.value_threshold', 500);
    }

    public function depositAmount(float $estimatedValue): float
    {
        $percent = $this->settings->getDecimal('rfq.commitment_deposit_percent', 5);

        return round($estimatedValue * $percent / 100, 2);
    }

    public function overageFee(): float
    {
        return $this->settings->getDecimal('rfq.overage_fee', 1);
    }
}
