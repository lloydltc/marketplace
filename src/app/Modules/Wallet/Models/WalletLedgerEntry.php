<?php

namespace App\Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Append-only ledger entry. Once written it is never updated or deleted.
 * `amount` is always positive; `direction` (credit/debit) gives the sign.
 */
class WalletLedgerEntry extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    // Append-only: created_at only, set by the DB / on insert.
    public $timestamps = false;

    protected $fillable = [
        'wallet_id',
        'type',
        'direction',
        'amount',
        'currency',
        'source_type',
        'source_id',
        'idempotency_key',
        'created_by',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $entry) {
            if (empty($entry->id)) {
                $entry->id = (string) Str::uuid();
            }
            if (empty($entry->created_at)) {
                $entry->created_at = now();
            }
        });

        // Hard guarantee of immutability.
        static::updating(fn () => throw new \RuntimeException('Ledger entries are append-only and cannot be modified.'));
        static::deleting(fn () => throw new \RuntimeException('Ledger entries are append-only and cannot be deleted.'));
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(VendorWallet::class, 'wallet_id');
    }

    public function isCredit(): bool
    {
        return $this->direction === 'credit';
    }

    public function signedAmount(): float
    {
        return $this->isCredit() ? (float) $this->amount : -(float) $this->amount;
    }
}
