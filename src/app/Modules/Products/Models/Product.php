<?php

namespace App\Modules\Products\Models;

use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Media\Models\ProductImage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vendor_id',
        'category_id',
        'title',
        'description',
        'sku',
        'price_zwl',
        'price_usd',
        'exchange_rate',
        'quantity',
        'status',
        'rating',
        'review_count',
        // Phase 7R additions
        'fulfilment_type',
        'cod_allowed',
    ];

    protected function casts(): array
    {
        return [
            'price_zwl'     => 'decimal:2',
            'price_usd'     => 'decimal:2',
            'exchange_rate' => 'decimal:4',
            'rating'        => 'decimal:2',
            'quantity'      => 'integer',
            'review_count'  => 'integer',
            'cod_allowed'   => 'boolean',
        ];
    }

    // ─── Pricing (USD is the entered price; ZWL is derived at the seller's rate) ──

    /** Primary, seller-entered price — always USD. */
    public function primaryPrice(): string
    {
        return 'USD ' . number_format((float) $this->price_usd, 2);
    }

    /** Derived ZWL price (price_usd × seller rate) — what the engine settles in. */
    public function convertedZwl(): string
    {
        return 'ZWL ' . number_format((float) $this->price_zwl, 2);
    }

    /** Human-readable rate, e.g. "1 USD = 36.5000 ZWL". */
    public function rateLabel(): ?string
    {
        return $this->exchange_rate !== null
            ? '1 USD = ' . rtrim(rtrim(number_format((float) $this->exchange_rate, 4), '0'), '.') . ' ZWL'
            : null;
    }

    protected static function newFactory(): Factory
    {
        return ProductFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (self $product) {
            if (empty($product->id)) {
                $product->id = (string) Str::uuid();
            }
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('display_order');
    }

    /** First image by display order. Uses the loaded `images` relation when
     *  eager-loaded (avoids N+1 on listing/landing pages). */
    public function coverImage(): ?ProductImage
    {
        return $this->images->first();
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

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeFbsEligible(Builder $query): Builder
    {
        return $query->whereIn('fulfilment_type', ['fbs', 'both']);
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

    public function isInStock(): bool
    {
        return $this->quantity > 0;
    }

    public function canBeEditedByVendor(): bool
    {
        return in_array($this->status, ['pending', 'inactive', 'rejected'], true);
    }

    // ─── Fulfilment (Phase 7R) ──────────────────────────────────────────────────

    public function supportsFbs(): bool
    {
        return in_array($this->fulfilment_type, ['fbs', 'both'], true);
    }

    public function supportsVendorFulfilment(): bool
    {
        return in_array($this->fulfilment_type, ['vendor', 'both'], true);
    }

    public function isCodAllowed(): bool
    {
        return (bool) $this->cod_allowed;
    }
}
