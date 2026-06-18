<?php

namespace App\Modules\Rfq\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RfqDeposit extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'part_request_id',
        'buyer_user_id',
        'amount',
        'currency',
        'merchant_reference',
        'gateway_ref',
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
        static::creating(function (self $deposit) {
            if (empty($deposit->id)) {
                $deposit->id = (string) Str::uuid();
            }
        });
    }

    public function partRequest(): BelongsTo
    {
        return $this->belongsTo(PartRequest::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['credited', 'refunded', 'forfeited', 'failed'], true);
    }
}
