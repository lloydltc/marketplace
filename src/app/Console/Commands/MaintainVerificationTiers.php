<?php

namespace App\Console\Commands;

use App\Models\Vendor;
use App\Modules\Verification\Models\VendorVerification;
use App\Modules\Verification\Services\TierEvaluator;
use App\Notifications\VerificationExpiringNotification;
use Illuminate\Console\Command;

/**
 * VB2: daily verification upkeep — recompute every approved vendor's badge tier
 * (so expired dimensions auto-demote) and remind vendors of dimensions expiring
 * within the reminder window.
 */
class MaintainVerificationTiers extends Command
{
    protected $signature = 'verification:maintain {--remind-days=14}';

    protected $description = 'Recompute badge tiers (auto-demote on expiry) and send re-verification reminders';

    public function handle(TierEvaluator $tiers): int
    {
        $recomputed = 0;
        Vendor::query()->whereNotNull('verification_tier')->orWhereHas('verifications')
            ->chunkById(100, function ($vendors) use ($tiers, &$recomputed) {
                foreach ($vendors as $vendor) {
                    $before = $vendor->verification_tier;
                    if ($tiers->recompute($vendor) !== $before) {
                        $recomputed++;
                    }
                }
            });

        $remindDays = (int) $this->option('remind-days');
        $reminded = 0;
        VendorVerification::query()
            ->where('status', 'approved')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($remindDays)])
            ->with('vendor')
            ->chunkById(100, function ($rows) use (&$reminded) {
                foreach ($rows as $row) {
                    $owner = $row->vendor?->users()->wherePivot('vendor_role', 'admin')->first();
                    $owner?->notify(new VerificationExpiringNotification($row));
                    $reminded++;
                }
            });

        $this->info("Recomputed {$recomputed} tier change(s); sent {$reminded} reminder(s).");

        return self::SUCCESS;
    }
}
