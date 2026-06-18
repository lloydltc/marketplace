<?php

namespace App\Modules\Wallet\Services;

use App\Models\Vendor;
use App\Modules\Settings\Services\SettingsService;
use App\Modules\Wallet\Exceptions\WalletBelowFloorException;
use App\Modules\Wallet\Models\VendorWallet;
use App\Modules\Wallet\Models\WalletLedgerEntry;
use Illuminate\Support\Facades\DB;

/**
 * The vendor wallet. Balance is ALWAYS derived from the append-only ledger:
 * cached_balance is a materialised convenience recomputed on every post and
 * provable against the entries (BUSINESS_MODEL.md §4).
 */
class WalletService
{
    /** Credit (+) types vs debit (−) types when not given explicitly. */
    private const DIRECTIONS = [
        'SALE_CREDIT'        => 'credit',
        'TOP_UP'             => 'credit',
        'COMMISSION_DEBIT'   => 'debit',
        'DELIVERY_FEE_DEBIT' => 'debit',
        'PAYOUT'             => 'debit',
    ];

    public function __construct(private readonly SettingsService $settings) {}

    public function walletFor(Vendor $vendor): VendorWallet
    {
        return VendorWallet::firstOrCreate(
            ['vendor_id' => $vendor->id],
            ['currency' => 'ZWL', 'cached_balance' => 0],
        );
    }

    /**
     * Append a ledger entry and recompute the cached balance.
     *
     * Idempotent on `idempotency_key`: if an entry with that key already exists,
     * nothing new is posted and the existing entry is returned.
     *
     * @param  array{direction?: string, source_type?: string, source_id?: ?string, idempotency_key?: ?string, created_by?: ?string, description?: ?string}  $opts
     */
    public function post(VendorWallet $wallet, string $type, float $amount, array $opts = []): WalletLedgerEntry
    {
        $key = $opts['idempotency_key'] ?? null;

        if ($key !== null) {
            $existing = WalletLedgerEntry::where('idempotency_key', $key)->first();
            if ($existing !== null) {
                return $existing;
            }
        }

        $direction = $opts['direction'] ?? (self::DIRECTIONS[$type] ?? 'credit');

        return DB::transaction(function () use ($wallet, $type, $amount, $direction, $opts, $key) {
            $entry = WalletLedgerEntry::create([
                'wallet_id'       => $wallet->id,
                'type'            => $type,
                'direction'       => $direction,
                'amount'          => round(abs($amount), 2),
                'currency'        => $wallet->currency,
                'source_type'     => $opts['source_type'] ?? null,
                'source_id'       => $opts['source_id'] ?? null,
                'idempotency_key' => $key,
                'created_by'      => $opts['created_by'] ?? null,
                'description'     => $opts['description'] ?? null,
            ]);

            $this->recalculate($wallet);
            $this->applyFloorEffects($wallet, $type);

            return $entry;
        });
    }

    /**
     * Recompute cached_balance from the ledger and stamp reconciled_at.
     * Returns the authoritative balance (credits − debits).
     */
    public function recalculate(VendorWallet $wallet): float
    {
        $credits = (float) $wallet->entries()->where('direction', 'credit')->sum('amount');
        $debits  = (float) $wallet->entries()->where('direction', 'debit')->sum('amount');
        $balance = round($credits - $debits, 2);

        $wallet->forceFill(['cached_balance' => $balance, 'reconciled_at' => now()])->save();

        return $balance;
    }

    public function floor(): float
    {
        return $this->settings->getDecimal('wallet.floor', 0);
    }

    public function isAboveFloor(Vendor $vendor): bool
    {
        return (float) $this->walletFor($vendor)->cached_balance >= $this->floor();
    }

    /**
     * Guard used at listing-creation time. Below floor → cannot list.
     */
    public function assertCanList(Vendor $vendor): void
    {
        if (! $this->isAboveFloor($vendor)) {
            throw new WalletBelowFloorException(
                'Your wallet balance is below the required minimum. Top up to add new listings.'
            );
        }
    }

    /**
     * Wallet standing drives VF-COD eligibility (BUSINESS_MODEL.md §3, §4):
     * drop below floor → revoke; top-up back to/above floor → reinstate.
     */
    private function applyFloorEffects(VendorWallet $wallet, string $type): void
    {
        $vendor  = $wallet->vendor;
        $balance = (float) $wallet->cached_balance;
        $floor   = $this->floor();

        if ($balance < $floor && $vendor->cod_eligible) {
            $vendor->update(['cod_eligible' => false]);
        } elseif ($type === 'TOP_UP' && $balance >= $floor && ! $vendor->cod_eligible) {
            $vendor->update(['cod_eligible' => true]);
        }
    }
}
