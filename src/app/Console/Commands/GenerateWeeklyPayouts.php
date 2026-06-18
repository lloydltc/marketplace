<?php

namespace App\Console\Commands;

use App\Modules\Wallet\Services\PayoutService;
use Illuminate\Console\Command;

class GenerateWeeklyPayouts extends Command
{
    protected $signature = 'wallet:generate-payouts';

    protected $description = 'Generate the weekly payout batch for vendors above the minimum payout amount';

    public function handle(PayoutService $payouts): int
    {
        $created = $payouts->generateWeeklyBatch();

        $this->info("Generated {$created->count()} pending payout(s).");

        return self::SUCCESS;
    }
}
