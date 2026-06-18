<?php

namespace App\Modules\Promotions\Services;

use App\Models\Vendor;
use App\Modules\Payments\Services\PesepayClient;
use App\Modules\Promotions\Models\PromotionPackage;
use App\Modules\Promotions\Models\PromotionPurchase;
use App\Modules\Promotions\Models\VendorPackageSubscription;
use App\Modules\Settings\Services\SettingsService;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Support\Str;

/**
 * Vehicle listing promotion (BUSINESS_MODEL.md §8) — the only paid path on the
 * lead-gen side (vehicles never check out). Featuring/bumping consumes a dealer
 * package credit when available, otherwise charges via Pesepay. Every purchase
 * is recorded for reporting. Expiry is automatic: ranking only boosts while
 * `featured_until` is in the future, so lapsed promotions demote with no job.
 */
class PromotionService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly PesepayClient $gateway
    ) {}

    // ─── Pricing (settings-driven) ───────────────────────────────────────────────

    public function featuredFee(): float
    {
        return $this->settings->getDecimal('promotion.featured_vehicle_fee', 10);
    }

    public function bumpFee(): float
    {
        return $this->settings->getDecimal('promotion.listing_bump_fee', 2);
    }

    public function badgeFee(): float
    {
        return $this->settings->getDecimal('promotion.verified_badge_fee', 20);
    }

    public function featuredDays(): int
    {
        return $this->settings->getInt('promotion.featured_vehicle_days', 7);
    }

    public function vendorIsVerified(Vendor $vendor): bool
    {
        return $vendor->documents()->where('status', 'approved')->exists();
    }

    // ─── Vendor-initiated promotions ─────────────────────────────────────────────

    /**
     * @return array{funded: string, redirectUrl: ?string}
     */
    public function feature(Vehicle $vehicle): array
    {
        return $this->promote($vehicle, 'featured', 'feature_credits_remaining', fn () => $this->applyFeatured($vehicle), $this->featuredFee());
    }

    public function bump(Vehicle $vehicle): array
    {
        return $this->promote($vehicle, 'bump', 'bump_credits_remaining', fn () => $this->applyBump($vehicle), $this->bumpFee());
    }

    /**
     * Verified-seller badge — gateway-only (no credit), gated on document
     * verification by the caller.
     *
     * @return array{funded: string, redirectUrl: ?string}
     */
    public function badge(Vehicle $vehicle, string $returnUrl, string $resultUrl): array
    {
        $purchase = $this->initiate($vehicle->vendor_id, 'badge', $this->badgeFee(), $vehicle->id, null, $returnUrl, $resultUrl);

        return ['funded' => 'gateway', 'redirectUrl' => $purchase->redirect_url];
    }

    public function buyPackage(Vendor $vendor, PromotionPackage $package, string $returnUrl, string $resultUrl): PromotionPurchase
    {
        return $this->initiate($vendor->id, 'package', (float) $package->price, null, $package->id, $returnUrl, $resultUrl);
    }

    /**
     * Shared feature/bump path: spend a package credit if available (free),
     * otherwise start a gateway purchase.
     *
     * @return array{funded: string, redirectUrl: ?string}
     */
    private function promote(Vehicle $vehicle, string $type, string $creditColumn, callable $apply, float $fee, ?string $returnUrl = null, ?string $resultUrl = null): array
    {
        $subscription = $this->activeSubscription($vehicle->vendor_id);

        if ($subscription !== null && $subscription->{$creditColumn} > 0) {
            $subscription->decrement($creditColumn);
            $apply();
            $this->record($vehicle->vendor_id, $type, 0, 'credit', $vehicle->id, null, 'completed');

            return ['funded' => 'credit', 'redirectUrl' => null];
        }

        $purchase = $this->initiate(
            $vehicle->vendor_id, $type, $fee, $vehicle->id, null,
            $returnUrl ?? route('vendor.vehicles.show', $vehicle),
            $resultUrl ?? route('payments.webhook'),
        );

        return ['funded' => 'gateway', 'redirectUrl' => $purchase->redirect_url];
    }

    // ─── Gateway plumbing ────────────────────────────────────────────────────────

    public function initiate(string $vendorId, string $type, float $amount, ?string $vehicleId, ?string $packageId, string $returnUrl, string $resultUrl): PromotionPurchase
    {
        $reference = 'PRM-' . strtoupper(Str::random(12));

        $purchase = $this->record($vendorId, $type, $amount, 'gateway', $vehicleId, $packageId, 'pending', $reference);

        $result = $this->gateway->initiateTransaction(
            amount: $amount,
            currency: 'ZWL',
            reason: 'SalmaDrive listing promotion (' . $type . ')',
            merchantReference: $reference,
            returnUrl: $returnUrl,
            resultUrl: $resultUrl,
        );

        $purchase->update([
            'gateway_ref'  => $result['referenceNumber'] ?: null,
            'redirect_url' => $result['redirectUrl'],
        ]);

        return $purchase;
    }

    public function confirm(PromotionPurchase $purchase, string $transactionStatus, ?string $rawPayload = null): void
    {
        $hash = $rawPayload !== null ? hash('sha256', $rawPayload) : null;

        if (in_array($purchase->status, ['completed', 'failed'], true)) {
            return;
        }
        if ($hash !== null && $purchase->webhook_payload_hash === $hash) {
            return;
        }

        if (strtoupper($transactionStatus) !== 'SUCCESS') {
            $purchase->update(['status' => 'failed', 'webhook_payload_hash' => $hash]);

            return;
        }

        $purchase->update(['status' => 'completed', 'completed_at' => now(), 'webhook_payload_hash' => $hash]);
        $this->applyPurchase($purchase);
    }

    private function applyPurchase(PromotionPurchase $purchase): void
    {
        match ($purchase->type) {
            'featured' => $this->applyFeatured($purchase->vehicle),
            'bump'     => $this->applyBump($purchase->vehicle),
            'badge'    => $this->applyBadge($purchase->vehicle),
            'package'  => $this->grantPackage($purchase),
            default    => null,
        };
    }

    // ─── Apply effects ───────────────────────────────────────────────────────────

    public function applyFeatured(Vehicle $vehicle): void
    {
        $vehicle->update(['featured_until' => now()->addDays($this->featuredDays())]);
    }

    public function applyBump(Vehicle $vehicle): void
    {
        $vehicle->update(['bumped_at' => now()]);
    }

    public function applyBadge(Vehicle $vehicle): void
    {
        $vehicle->update(['seller_verified_badge' => true]);
    }

    private function grantPackage(PromotionPurchase $purchase): void
    {
        $package = $purchase->package;
        if ($package === null) {
            return;
        }

        VendorPackageSubscription::create([
            'vendor_id'                 => $purchase->vendor_id,
            'package_id'                => $package->id,
            'listing_credits_remaining' => $package->listing_credits,
            'feature_credits_remaining' => $package->feature_credits,
            'bump_credits_remaining'    => $package->bump_credits,
            'status'                    => 'active',
            'expires_at'                => now()->addDays($package->duration_days),
        ]);
    }

    public function activeSubscription(string $vendorId): ?VendorPackageSubscription
    {
        return VendorPackageSubscription::activeFor($vendorId)->latest()->first();
    }

    private function record(string $vendorId, string $type, float $amount, string $fundedBy, ?string $vehicleId, ?string $packageId, string $status, ?string $reference = null): PromotionPurchase
    {
        return PromotionPurchase::create([
            'vendor_id'          => $vendorId,
            'vehicle_id'         => $vehicleId,
            'package_id'         => $packageId,
            'type'               => $type,
            'amount'             => $amount,
            'funded_by'          => $fundedBy,
            'status'             => $status,
            'merchant_reference' => $reference,
            'completed_at'       => $status === 'completed' ? now() : null,
        ]);
    }
}
