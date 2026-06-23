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
        // D5 lifecycle
        'published_at',
        'expires_at',
        'renewed_at',
        'expiry_count',
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
            'published_at'          => 'datetime',
            'expires_at'            => 'datetime',
            'renewed_at'            => 'datetime',
            'expiry_count'          => 'integer',
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

    /** First image by display order. Uses the loaded `images` relation when
     *  eager-loaded (avoids N+1 on listing/landing pages). */
    public function coverImage(): ?VehicleImage
    {
        return $this->images->first();
    }

    // ─── Dynamic features (D4) ──────────────────────────────────────────────────

    public function featureValues(): HasMany
    {
        return $this->hasMany(VehicleFeatureValue::class);
    }

    /** The stored value for a feature definition id, or null (uses loaded relation). */
    public function featureValueFor(string $definitionId): ?VehicleFeatureValue
    {
        return $this->featureValues->firstWhere('feature_definition_id', $definitionId);
    }

    /**
     * Feature values grouped by their definition's display group, for the buyer
     * spec sheet. Returns [group => [VehicleFeatureValue, …]].
     */
    public function groupedFeatures(): array
    {
        return $this->featureValues
            ->filter(fn ($fv) => $fv->definition !== null && $fv->definition->is_active)
            ->sortBy(fn ($fv) => [$fv->definition->group, $fv->definition->sort_order, $fv->definition->name])
            ->groupBy(fn ($fv) => $fv->definition->group ?: 'Features')
            ->map(fn ($g) => $g->values()->all())
            ->all();
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

    // ─── Lifecycle (D5) ─────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /** Active listing whose expiry time has passed (awaiting the sweep job). */
    public function hasLapsed(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function scopeExpiringBetween(Builder $query, \DateTimeInterface $from, \DateTimeInterface $to): Builder
    {
        return $query->where('status', 'active')->whereBetween('expires_at', [$from, $to]);
    }

    /** The user to notify about this listing (private seller, or the vendor's admin). */
    public function ownerUser(): ?\App\Models\User
    {
        return $this->user_id !== null
            ? $this->seller
            : $this->vendor?->users()->wherePivot('vendor_role', 'admin')->first();
    }

    /** Seller contact details revealed to a buyer on contact (D6). */
    public function contactDetails(): array
    {
        if ($this->vendor_id !== null) {
            return ['name' => $this->vendor?->name, 'phone' => $this->vendor?->phone, 'email' => $this->vendor?->contact_email];
        }

        return ['name' => $this->seller?->name, 'phone' => $this->seller?->contact_phone, 'email' => $this->seller?->email];
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

    // ─── Pricing (either currency; sellers aren't forced to price in ZWL) ────────

    public function hasUsd(): bool
    {
        return $this->price_usd !== null;
    }

    public function hasZwl(): bool
    {
        return $this->price_zwl !== null;
    }

    /** Primary price line — USD preferred when present, else ZWL. */
    public function primaryPrice(): string
    {
        if ($this->price_usd !== null) {
            return 'USD ' . number_format((float) $this->price_usd, 2);
        }
        if ($this->price_zwl !== null) {
            return 'ZWL ' . number_format((float) $this->price_zwl, 2);
        }

        return 'Price on request';
    }

    /** Secondary price line (the other currency) when both are set, else null. */
    public function secondaryPrice(): ?string
    {
        if ($this->price_usd !== null && $this->price_zwl !== null) {
            return 'ZWL ' . number_format((float) $this->price_zwl, 2);
        }

        return null;
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
