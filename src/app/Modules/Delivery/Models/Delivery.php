<?php

namespace App\Modules\Delivery\Models;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Delivery extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'rider_id',
        'zone_id',
        'cash_session_id',
        'status',
        'cod_expected',
        'cod_collected',
        'proof_photo_path',
        'proof_note',
        'assigned_at',
        'picked_up_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'cod_expected'  => 'decimal:2',
            'cod_collected' => 'decimal:2',
            'assigned_at'   => 'datetime',
            'picked_up_at'  => 'datetime',
            'delivered_at'  => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $delivery) {
            if (empty($delivery->id)) {
                $delivery->id = (string) Str::uuid();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'zone_id');
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(RiderCashSession::class, 'cash_session_id');
    }

    public function isCod(): bool
    {
        return (float) $this->cod_expected > 0;
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }
}
