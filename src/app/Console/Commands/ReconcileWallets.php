<?php

namespace App\Console\Commands;

use App\Modules\Wallet\Models\VendorWallet;
use App\Modules\Wallet\Services\ReconciliationService;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Reconciliation job (BUSINESS_MODEL.md §4, P4): prove the three money sources of
 * truth agree — cached balance == ledger sum, every paid gateway top-up is booked
 * to the ledger, and no ledger top-up credit is orphaned. Read-only by default
 * (so it can ALARM rather than silently self-heal); pass --fix to recompute
 * drifted cached balances from the authoritative ledger.
 */
class ReconcileWallets extends Command
{
    protected $signature = 'wallet:reconcile {--fix : Recompute drifted cached balances from the ledger}';

    protected $description = 'Reconcile vendor wallets (ledger ↔ cached balance ↔ gateway top-ups) and alarm on drift';

    public function handle(ReconciliationService $reconciler, WalletService $wallet): int
    {
        $report = $reconciler->run();
        $discrepancies = $report['discrepancies'];

        if ($discrepancies === []) {
            $this->info("All {$report['checked']} wallet(s) reconciled — no drift.");

            return self::SUCCESS;
        }

        // Drift is a money-integrity alarm — log loudly for monitoring/alerting.
        Log::critical('Wallet reconciliation found discrepancies', [
            'count'         => count($discrepancies),
            'discrepancies' => $discrepancies,
        ]);

        foreach ($discrepancies as $d) {
            $this->error('[' . $d['type'] . '] ' . json_encode($d));
        }

        if ($this->option('fix')) {
            $fixed = 0;
            foreach ($discrepancies as $d) {
                if ($d['type'] === 'balance_drift' && ($w = VendorWallet::find($d['wallet_id']))) {
                    $wallet->recalculate($w);
                    $fixed++;
                }
            }
            $this->warn("Recomputed {$fixed} drifted balance(s). Non-balance discrepancies need manual review.");
        }

        $this->error(count($discrepancies) . ' discrepancy(ies) found.');

        // Non-zero exit so schedulers/monitors treat this as a failure to alert on.
        return self::FAILURE;
    }
}
