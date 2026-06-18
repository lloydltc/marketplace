<?php

namespace App\Modules\Wallet\Services;

use App\Modules\Wallet\Models\VendorWallet;
use App\Modules\Wallet\Models\WalletLedgerEntry;
use App\Modules\Wallet\Models\WalletTopUp;

/**
 * P4: read-only money reconciliation. Detects drift between the three sources of
 * truth that must always agree:
 *   1. a wallet's cached_balance  ==  (credits − debits) over its ledger;
 *   2. every PAID gateway top-up has a matching TOP_UP ledger credit (money
 *      received was actually booked);
 *   3. no TOP_UP ledger credit exists without a paid top-up behind it.
 *
 * It never mutates state — it returns a structured report so a command/monitor
 * can alarm on drift. Fixing is a deliberate, separate action.
 */
class ReconciliationService
{
    /**
     * @return array{checked: int, discrepancies: array<int, array<string, mixed>>}
     */
    public function run(): array
    {
        $discrepancies = [];

        // 1. Cached balance must equal the signed ledger sum.
        $wallets = VendorWallet::query()->get();
        foreach ($wallets as $wallet) {
            $expected = $this->ledgerBalance($wallet->id);
            $cached   = round((float) $wallet->cached_balance, 2);

            if ($expected !== $cached) {
                $discrepancies[] = [
                    'type'      => 'balance_drift',
                    'wallet_id' => $wallet->id,
                    'vendor_id' => $wallet->vendor_id,
                    'cached'    => $cached,
                    'expected'  => $expected,
                    'delta'     => round($cached - $expected, 2),
                ];
            }
        }

        // 2. Each paid top-up must have a TOP_UP ledger credit.
        $paidTopUps = WalletTopUp::query()->where('status', 'paid')->get();
        foreach ($paidTopUps as $topUp) {
            $exists = WalletLedgerEntry::query()
                ->where('type', 'TOP_UP')
                ->where('source_type', 'wallet_top_up')
                ->where('source_id', $topUp->id)
                ->exists();

            if (! $exists) {
                $discrepancies[] = [
                    'type'        => 'unbooked_topup',
                    'top_up_id'   => $topUp->id,
                    'vendor_id'   => $topUp->vendor_id,
                    'amount'      => (float) $topUp->amount,
                    'gateway_ref' => $topUp->gateway_ref,
                ];
            }
        }

        // 3. Each TOP_UP ledger credit must trace back to a paid top-up.
        $topUpEntries = WalletLedgerEntry::query()
            ->where('type', 'TOP_UP')
            ->where('source_type', 'wallet_top_up')
            ->get();
        foreach ($topUpEntries as $entry) {
            $ok = WalletTopUp::query()
                ->whereKey($entry->source_id)
                ->where('status', 'paid')
                ->exists();

            if (! $ok) {
                $discrepancies[] = [
                    'type'      => 'orphan_topup_credit',
                    'entry_id'  => $entry->id,
                    'source_id' => $entry->source_id,
                    'amount'    => (float) $entry->amount,
                ];
            }
        }

        return [
            'checked'       => $wallets->count(),
            'discrepancies' => $discrepancies,
        ];
    }

    /** Authoritative signed balance (credits − debits) straight from the ledger. */
    public function ledgerBalance(string $walletId): float
    {
        $credits = (float) WalletLedgerEntry::where('wallet_id', $walletId)->where('direction', 'credit')->sum('amount');
        $debits  = (float) WalletLedgerEntry::where('wallet_id', $walletId)->where('direction', 'debit')->sum('amount');

        return round($credits - $debits, 2);
    }
}
