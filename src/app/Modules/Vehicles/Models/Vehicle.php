<?php

namespace App\Modules\Vehicles\Models;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Media\Models\VehicleImage;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vendor_id',
        'user_id',
        'make_id',
        'model_id',
        'year',
        'body_type',
        'transmission',
        'fuel_type',
        'engine_cc',
        'mileage',
        'vin',
        'color',
        'condition',
        'status',
        'price_zwl',
        'price_usd',
        'description',
        'rating',
        'review_count',
        // Phase 7R additions (promotion / lead-gen monetisation)
        'featured_until',
        'bumped_at',
        'listing_package_id',
        'seller_verified_badge',
    ];

    protected function casts(): array
    {
        return [
            'year'                  => 'integer',
            'engine_cc'             => 'integer',
            'mileage'               => 'integer',
            'price_zwl'             => 'decimal:2',
            'price_usd'             => 'decimal:2',
            'rating'                => 'decimal:2',
            'review_count'          => 'integer',
            'featured_until'        => 'datetime',
            'bumped_at'             => 'datetime',
            'seller_verified_badge' => 'boolean',
        ];
    }

    protected static function newFactory(): Factory
    {
        return VehicleFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (self $vehicle) {
            if (empty($vehicle->id)) {
                $vehicle->id = (string) Str::uuid();
            }
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'make_id');
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class)->orderBy('display_order');
    }

    public function coverImage(): ?VehicleImage
    {
        return $this->images()->first();
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeForVendor(Builder $query, string $vendorId): Builder
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeForSeller(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured_until', '>', now());
    }

    // ─── Status helpers ───────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['pending', 'inactive', 'rejected'], true);
    }

    public function isListedByVendor(): bool
    {
        return $this->vendor_id !== null;
    }

    public function isListedByPrivateSeller(): bool
    {
        return $this->user_id !== null;
    }

    public function displayTitle(): string
    {
        $make  = $this->make?->name  ?? 'Unknown';
        $model = $this->vehicleModel?->name ?? 'Unknown';

        return "{$this->year} {$make} {$model}";
    }

    // ─── Promotion (Phase 7R — lead-gen monetisation, BUSINESS_MODEL.md §8) ─────

    public function isFeatured(): bool
    {
        return $this->featured_until !== null && $this->featured_until->isFuture();
    }

    public function hasVerifiedSellerBadge(): bool
    {
        return (bool) $this->seller_verified_badge;
    }

    /**
     * Whether the listing's owner (vendor or private seller) is verified.
     * Drives the "Unverified seller" badge shown to buyers (remediation R4).
     */
    public function ownerIsVerified(): bool
    {
        if ($this->vendor_id !== null) {
            return $this->vendor?->status === 'approved';
        }

        if ($this->user_id !== null) {
            return $this->seller?->status === 'active';
        }

        return true;
    }
}
