<?php

namespace App\Console\Commands;

use App\Modules\Verification\Services\FraudRuleService;
use Illuminate\Console\Command;

/**
 * VB4: deterministic fraud scan (duplicate photos, rapid relist) → moderation
 * queue. Idempotent; scheduled alongside the H11 content scan.
 */
class ScanFraudSignals extends Command
{
    protected $signature = 'fraud:scan';

    protected $description = 'Rule-based fraud scan (duplicate photos, rapid relist) into the moderation queue';

    public function handle(FraudRuleService $fraud): int
    {
        $created = $fraud->scan();
        $this->info("Opened {$created} fraud auto-flag(s).");

        return self::SUCCESS;
    }
}
