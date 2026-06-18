<?php

namespace App\Modules\Promotions\Models;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VendorPackageSubscription extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vendor_id',
        'package_id',
        'listing_credits_remaining',
        'feature_credits_remaining',
        'bump_credits_remaining',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'listing_credits_remaining' => 'integer',
            'feature_credits_remaining' => 'integer',
            'bump_credits_remaining'    => 'integer',
            'expires_at'                => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $sub) {
            if (empty($sub->id)) {
                $sub->id = (string) Str::uuid();
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(PromotionPackage::class, 'package_id');
    }

    public function scopeActiveFor(Builder $query, string $vendorId): Builder
    {
        return $query->where('vendor_id', $vendorId)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isLive(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
