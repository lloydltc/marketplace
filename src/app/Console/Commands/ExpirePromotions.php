<?php

namespace App\Console\Commands;

use App\Modules\Promotions\Models\VendorPackageSubscription;
use Illuminate\Console\Command;

/**
 * Phase 17: lapse expired package subscriptions. Featured/bump demotion needs no
 * job — search ranking only boosts while featured_until is in the future, so a
 * lapsed featured listing demotes automatically.
 */
class ExpirePromotions extends Command
{
    protected $signature = 'promotions:expire';

    protected $description = 'Mark expired dealer package subscriptions as expired';

    public function handle(): int
    {
        $count = VendorPackageSubscription::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} package subscription(s).");

        return self::SUCCESS;
    }
}
