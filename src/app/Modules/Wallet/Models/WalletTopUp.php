<?php

namespace App\Modules\Wallet\Models;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WalletTopUp extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vendor_id',
        'merchant_reference',
        'gateway_ref',
        'amount',
        'currency',
        'status',
        'redirect_url',
        'webhook_payload_hash',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'  => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $topUp) {
            if (empty($topUp->id)) {
                $topUp->id = (string) Str::uuid();
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
