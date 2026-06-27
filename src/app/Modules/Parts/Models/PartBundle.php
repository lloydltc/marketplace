<?php

namespace App\Modules\Parts\Models;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/** PM6: a sellable composite of offerings (service kit). */
class PartBundle extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['vendor_id', 'name', 'slug', 'description', 'price_usd', 'is_service_kit', 'status'];

    protected $attributes = ['status' => 'active', 'is_service_kit' => true];

    protected function casts(): array
    {
        return ['price_usd' => 'decimal:2', 'is_service_kit' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $b) {
            if (empty($b->id)) {
                $b->id = (string) Str::uuid();
            }
            if (empty($b->slug)) {
                $b->slug = static::uniqueSlug($b->name);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'kit';
        $slug = $base;
        $i = 1;
        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    public function items(): HasMany
    {
        return $this->hasMany(PartBundleItem::class, 'bundle_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Set price when defined, else the summed price of its components. */
    public function effectivePrice(): float
    {
        if ($this->price_usd !== null) {
            return (float) $this->price_usd;
        }

        return (float) $this->items->sum(fn ($item) => (float) ($item->product?->price_usd ?? 0) * $item->qty);
    }

    /** Every component has enough stock for its quantity. */
    public function isInStock(): bool
    {
        if ($this->items->isEmpty()) {
            return false;
        }

        return $this->items->every(fn ($item) => $item->product !== null
            && $item->product->isActive()
            && $item->product->quantity >= $item->qty);
    }
}
