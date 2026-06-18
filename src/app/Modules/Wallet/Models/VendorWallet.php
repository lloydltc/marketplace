<?php

namespace App\Modules\Wallet\Models;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VendorWallet extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vendor_id',
        'currency',
        'cached_balance',
        'reconciled_at',
    ];

    protected function casts(): array
    {
        return [
            'cached_balance' => 'decimal:2',
            'reconciled_at'  => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $wallet) {
            if (empty($wallet->id)) {
                $wallet->id = (string) Str::uuid();
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(WalletLedgerEntry::class, 'wallet_id');
    }

    public function balance(): float
    {
        return (float) $this->cached_balance;
    }
}
