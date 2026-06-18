<?php

namespace App\Modules\Concierge\Models;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ConciergeRequest extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'buyer_user_id',
        'make_id',
        'model_id',
        'year',
        'part_description',
        'location',
        'notes',
        'status',
        'part_value',
        'service_fee',
        'delivery_fee',
        'total',
        'currency',
        'sourced_vendor_id',
        'settled_at',
        'merchant_reference',
        'gateway_ref',
        'payment_status',
        'redirect_url',
        'webhook_payload_hash',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'year'         => 'integer',
            'part_value'   => 'decimal:2',
            'service_fee'  => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total'        => 'decimal:2',
            'settled_at'   => 'datetime',
            'paid_at'      => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $request) {
            if (empty($request->id)) {
                $request->id = (string) Str::uuid();
            }
        });
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function sourcedVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'sourced_vendor_id');
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'make_id');
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === 'quoted' && ! $this->isPaid();
    }
}
