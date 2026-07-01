<?php

namespace Database\Seeders;

use App\Modules\Settings\Models\PlatformSetting;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Database\Seeder;

/**
 * Seeds the default pricing schedule from BUSINESS_MODEL.md §9. Idempotent:
 * uses firstOrCreate keyed on `key`, so re-seeding never clobbers admin edits.
 * All values are starting hypotheses, tunable in the admin settings UI.
 */
class PlatformSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // [key, value, type, group, description]
        $defaults = [
            // Commission (§5, §9)
            ['commission.default_rate', '10', 'decimal', 'commission', 'Platform commission on parts sales (%). Overridden by category then vendor.'],

            // Delivery (§3, §9) — zone table arrives in Phase 14; this is the launch flat fee.
            ['delivery.fbs_default_fee', '5.00', 'decimal', 'delivery', 'Default Fulfilled-by-Salma delivery fee (USD) until delivery zones are configured.'],
            ['delivery.vf_auto_complete_days', '7', 'integer', 'delivery', 'Days after delivery before a vendor-fulfilled order auto-completes if the buyer does not confirm.'],

            // Concierge (§6 Tier 2, §9)
            ['concierge.fee_minimum', '5.00', 'decimal', 'concierge', 'Minimum concierge service fee (USD).'],
            ['concierge.fee_percent', '10', 'decimal', 'concierge', 'Concierge service fee as a percentage of part value.'],

            // RFQ fair-use (§6 Tier 1, §9) — thresholds launch OFF (§10).
            ['rfq.free_quota_monthly', '3', 'integer', 'rfq', 'Free public RFQ requests per buyer per month.'],
            ['rfq.overage_fee', '1.00', 'decimal', 'rfq', 'Fee (USD) per RFQ request beyond the free monthly quota.'],
            ['rfq.value_threshold', '500.00', 'decimal', 'rfq', 'Estimated value (USD) above which a commitment deposit is required.'],
            ['rfq.commitment_deposit_percent', '5', 'decimal', 'rfq', 'Refundable commitment deposit as a percentage of estimated value.'],
            ['rfq.thresholds_enabled', '0', 'boolean', 'rfq', 'Master switch for RFQ fair-use thresholds. Launch OFF; enable once abuse is observed.'],

            // Wallet & payouts (§4, §9)
            ['wallet.floor', '0.00', 'decimal', 'wallet', 'Wallet balance floor (USD). Below this, new listings and VF-COD are suspended.'],
            ['wallet.payout_minimum', '10.00', 'decimal', 'wallet', 'Minimum payout amount (USD); balances below this roll over.'],
            ['wallet.payout_cycle', 'weekly', 'string', 'wallet', 'Vendor payout cadence.'],

            // Vehicle promotion (§8, §9)
            ['promotion.featured_vehicle_fee', '10.00', 'decimal', 'promotion', 'Price (USD) for a featured vehicle listing.'],
            ['promotion.featured_vehicle_days', '7', 'integer', 'promotion', 'Duration (days) of a featured vehicle listing.'],
            ['promotion.listing_bump_fee', '2.00', 'decimal', 'promotion', 'Price (USD) to bump a vehicle listing to the top of the recency feed.'],
            ['promotion.verified_badge_fee', '20.00', 'decimal', 'promotion', 'Price (USD) for a verified-seller badge on a listing (requires approved documents).'],

            // Search ranking weights (§3, §8) — settable to zero to disable boosts.
            ['search.fbs_placement_boost', '10', 'decimal', 'search', 'Ranking boost applied to FBS-eligible products. Set 0 to disable.'],
            ['search.featured_vehicle_boost', '100', 'decimal', 'search', 'Ranking boost applied to featured vehicle listings. Set 0 to disable.'],

            // Seller verification gating (remediation R4)
            ['sellers.unverified_can_transact', '0', 'boolean', 'sellers', 'Allow listings from unverified/pending sellers to be purchased. Off = display-only (visible with an Unverified badge, but buying disabled) until approved.'],

            // Listing lifecycle (D5) — vehicles are lead-gen and time-bound.
            ['listings.vehicle_expiry_enabled', '1', 'boolean', 'listings', 'Expire vehicle listings after the configured period. Off = listings never expire.'],
            ['listings.vehicle_expiry_days', '60', 'integer', 'listings', 'Days a vehicle listing stays live before expiring (renewable).'],
            ['listings.expiry_soon_days', '7', 'integer', 'listings', 'Days before expiry to start showing buyer countdowns and seller renew prompts.'],
            ['listings.vehicle_renewal_fee', '0.00', 'decimal', 'listings', 'Price (USD) to renew an expired vehicle listing. 0 = free renewal (launch default).'],
            ['history.report_price_usd', '5.00', 'decimal', 'history', 'Price (USD) for a full vehicle history report (HR4).'],
            ['inspection.fee_usd', '30.00', 'decimal', 'inspection', 'Fee (USD) for a vehicle inspection booking (TI5).'],

            // COD matrix rollout flags (§3, §10)
            ['cod.fbs_enabled', '1', 'boolean', 'cod', 'Allow cash on delivery for Fulfilled-by-Salma orders.'],
            ['cod.vf_enabled', '0', 'boolean', 'cod', 'Allow cash on delivery for vendor-fulfilled orders. Launch OFF (enable after wallet floor enforcement is live).'],
        ];

        foreach ($defaults as [$key, $value, $type, $group, $description]) {
            PlatformSetting::firstOrCreate(
                ['key' => $key],
                [
                    'value'       => $value,
                    'type'        => $type,
                    'group'       => $group,
                    'description' => $description,
                ]
            );
        }

        app(SettingsService::class)->flush();
    }
}
