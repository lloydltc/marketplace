<?php

namespace App\Modules\Promotions\Models;

use App\Models\Vendor;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PromotionPurchase extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vendor_id',
        'vehicle_id',
        'package_id',
        'type',
        'amount',
        'currency',
        'status',
        'funded_by',
        'merchant_reference',
        'gateway_ref',
        'redirect_url',
        'webhook_payload_hash',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $purchase) {
            if (empty($purchase->id)) {
                $purchase->id = (string) Str::uuid();
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(PromotionPackage::class, 'package_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
