<?php

namespace App\Modules\Payments\Models;

use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'gateway',
        'merchant_reference',
        'gateway_ref',
        'method',
        'amount',
        'currency',
        'status',
        'redirect_url',
        'poll_url',
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
        static::creating(function (self $payment) {
            if (empty($payment->id)) {
                $payment->id = (string) Str::uuid();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['paid', 'failed', 'cancelled'], true);
    }
}
