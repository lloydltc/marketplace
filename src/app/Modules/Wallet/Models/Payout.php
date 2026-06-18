<?php

namespace App\Modules\Wallet\Models;

use App\Models\Vendor;
use App\Models\VendorBankAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payout extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vendor_id',
        'amount',
        'currency',
        'period_start',
        'period_end',
        'bank_account_id',
        'status',
        'reference',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'period_start' => 'date',
            'period_end'   => 'date',
            'approved_at'  => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $payout) {
            if (empty($payout->id)) {
                $payout->id = (string) Str::uuid();
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(VendorBankAccount::class, 'bank_account_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
