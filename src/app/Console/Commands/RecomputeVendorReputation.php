<?php

namespace App\Console\Commands;

use App\Models\Vendor;
use App\Modules\Verification\Services\ReputationService;
use Illuminate\Console\Command;

/**
 * VB3: recompute reputation scores for approved vendors (also re-evaluates the
 * Top-Rated tier, since it depends on the score). Scheduled daily.
 */
class RecomputeVendorReputation extends Command
{
    protected $signature = 'reputation:recompute';

    protected $description = 'Recompute vendor reputation scores and dependent badge tiers';

    public function handle(ReputationService $reputation): int
    {
        $count = 0;
        Vendor::query()->where('status', 'approved')->chunkById(100, function ($vendors) use ($reputation, &$count) {
            foreach ($vendors as $vendor) {
                $reputation->recompute($vendor);
                $count++;
            }
        });

        $this->info("Recomputed reputation for {$count} vendor(s).");

        return self::SUCCESS;
    }
}
