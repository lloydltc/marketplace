<?php

namespace App\Models;

use App\Modules\Products\Models\Product;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'description',
        'contact_email',
        'phone',
        'address',
        'verified_at',
        'suspended_at',
        // Phase 3 additions
        'status',
        'tier',
        'featured_until',
        // VB1: trust-badge tier (computed) + admin manual grant + cached reputation
        'verification_tier',
        'manual_tier',
        'reputation_score',
        'commission_rate',
        'business_registration',
        'tax_id',
        // Phase 7R additions
        'default_fulfilment',
        'cod_eligible',
    ];

    protected function casts(): array
    {
        return [
            'verified_at'     => 'datetime',
            'suspended_at'    => 'datetime',
            'featured_until'  => 'datetime',
            'commission_rate' => 'float',
            'cod_eligible'    => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $vendor) {
            if (empty($vendor->id)) {
                $vendor->id = (string) Str::uuid();
            }

            if (empty($vendor->slug)) {
                $vendor->slug = Str::slug($vendor->name);
            }
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'vendor_users')
            ->withPivot(['vendor_role', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    public function admins(): BelongsToMany
    {
        return $this->users()->wherePivot('vendor_role', 'admin');
    }

    public function workers(): BelongsToMany
    {
        return $this->users()->wherePivot('vendor_role', 'worker');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(VendorBankAccount::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    /** VB1/VB2: per-dimension verification decisions. */
    public function verifications(): HasMany
    {
        return $this->hasMany(\App\Modules\Verification\Models\VendorVerification::class);
    }

    /**
     * VB1: dimensions currently approved & unexpired.
     *
     * @return list<string>
     */
    public function validVerificationDimensions(): array
    {
        return $this->verifications()->valid()->pluck('dimension')->unique()->values()->all();
    }

    /** VB1: config for the vendor's computed primary badge tier, or null. */
    public function badgeTierConfig(): ?array
    {
        return $this->verification_tier
            ? config('verification.tiers.' . $this->verification_tier)
            : null;
    }

    /** VB1: short label for the current trust badge (e.g. "Premium Dealer"), or null. */
    public function badgeTierLabel(): ?string
    {
        return $this->badgeTierConfig()['label'] ?? null;
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /** H8: dealers with live paid placement. */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured_until', '>', now());
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Whether this vendor's listings may be transacted (bought/checked out).
     * Approved vendors always can; unverified/pending vendors only if the
     * platform flag allows it (remediation R4 — default: display-only).
     */
    public function canTransact(): bool
    {
        if ($this->isApproved()) {
            return true;
        }

        return app(\App\Modules\Settings\Services\SettingsService::class)
            ->getBool('sellers.unverified_can_transact');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isPremium(): bool
    {
        return $this->tier === 'premium';
    }

    /** H8: paid featured-dealer placement currently active. */
    public function isFeaturedDealer(): bool
    {
        return $this->featured_until !== null && $this->featured_until->isFuture();
    }

    /** Public storefront URL for this dealer. */
    public function storefrontUrl(): string
    {
        return route('dealers.show', $this->slug);
    }

    /** Resolved logo URL, or null when none uploaded. */
    public function logoUrl(): ?string
    {
        if (empty($this->logo)) {
            return null;
        }

        // Absolute URLs pass through; stored paths resolve against the public disk.
        return Str::startsWith($this->logo, ['http://', 'https://'])
            ? $this->logo
            : \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo);
    }

    public function isUnverified(): bool
    {
        return $this->tier === 'unverified';
    }

    // ─── Fulfilment (Phase 7R) ──────────────────────────────────────────────────

    public function supportsFbs(): bool
    {
        return in_array($this->default_fulfilment, ['fbs', 'both'], true);
    }

    public function supportsVendorFulfilment(): bool
    {
        return in_array($this->default_fulfilment, ['vendor', 'both'], true);
    }

    public function isCodEligible(): bool
    {
        return (bool) $this->cod_eligible;
    }
}
