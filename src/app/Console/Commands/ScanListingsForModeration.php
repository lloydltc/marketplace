<?php

namespace App\Console\Commands;

use App\Modules\Moderation\Services\ModerationRuleService;
use Illuminate\Console\Command;

/**
 * H11: run the rule-based auto-flag scan (no AI). Opens moderation reports for
 * listings that trip a deterministic rule. Idempotent — safe to run on a schedule.
 */
class ScanListingsForModeration extends Command
{
    protected $signature = 'moderation:scan';

    protected $description = 'Rule-based auto-flag scan of live listings for the moderation queue';

    public function handle(ModerationRuleService $rules): int
    {
        $created = $rules->scan();

        $this->info("Opened {$created} auto-flag report(s).");

        return self::SUCCESS;
    }
}
