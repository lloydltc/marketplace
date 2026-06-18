<?php

namespace App\Modules\Wallet\Services;

use App\Models\User;
use App\Modules\Settings\Services\SettingsService;
use App\Modules\Wallet\Models\Payout;
use App\Modules\Wallet\Models\VendorWallet;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Weekly payout cycle (BUSINESS_MODEL.md §4). Generation snapshots each vendor's
 * payable balance (only at/above the configured minimum; smaller balances roll
 * over). An admin approves, which posts the PAYOUT ledger debit; the bank
 * reference is recorded when the transfer is made.
 */
class PayoutService
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly SettingsService $settings
    ) {}

    /**
     * Create pending payouts for every eligible vendor. Idempotent per vendor:
     * a vendor with an existing pending payout is skipped.
     *
     * @return Collection<int, Payout>
     */
    public function generateWeeklyBatch(?CarbonImmutable $end = null): Collection
    {
        $end     = $end ?? CarbonImmutable::now();
        $start   = $end->subWeek();
        $minimum = $this->settings->getDecimal('wallet.payout_minimum', 10);

        $created = collect();

        VendorWallet::query()
            ->where('cached_balance', '>=', $minimum)
            ->get()
            ->each(function (VendorWallet $wallet) use ($start, $end, $created) {
                if (Payout::where('vendor_id', $wallet->vendor_id)->where('status', 'pending')->exists()) {
                    return; // balance rolls over into the existing pending payout
                }

                $bankAccount = $wallet->vendor->bankAccounts()
                    ->whereNotNull('verified_at')
                    ->first();

                $created->push(Payout::create([
                    'vendor_id'       => $wallet->vendor_id,
                    'amount'          => $wallet->cached_balance,
                    'currency'        => $wallet->currency,
                    'period_start'    => $start->toDateString(),
                    'period_end'      => $end->toDateString(),
                    'bank_account_id' => $bankAccount?->id,
                    'status'          => 'pending',
                ]));
            });

        return $created;
    }

    /**
     * Approve a pending payout: post the PAYOUT debit (idempotent) and stamp it.
     */
    public function approve(Payout $payout, User $admin): void
    {
        if (! $payout->isPending()) {
            return;
        }

        $wallet = $this->wallet->walletFor($payout->vendor);

        $this->wallet->post($wallet, 'PAYOUT', (float) $payout->amount, [
            'source_type'     => 'payout',
            'source_id'       => $payout->id,
            'idempotency_key' => 'payout:' . $payout->id,
            'created_by'      => $admin->id,
            'description'     => 'Weekly payout ' . $payout->period_start->format('d M') . '–' . $payout->period_end->format('d M Y'),
        ]);

        $payout->update([
            'status'      => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);
    }

    public function markPaid(Payout $payout, string $reference): void
    {
        $payout->update(['status' => 'paid', 'reference' => $reference]);
    }

    public function reject(Payout $payout): void
    {
        if ($payout->isPending()) {
            $payout->update(['status' => 'rejected']);
        }
    }
}
