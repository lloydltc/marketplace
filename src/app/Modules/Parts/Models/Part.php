<?php

namespace App\Modules\Parts\Models;

use App\Modules\Categories\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * PM1: a canonical catalog part — authored once. Vendor sellable listings
 * (offerings) live on the existing `products` table and link here via part_id (PM2).
 */
class Part extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'slug',
        'name',
        'brand',
        'category_id',
        'primary_oem',
        'description',
        'warranty_months',
        'warranty_terms',
        'is_universal',
        'status',
    ];

    protected $attributes = [
        'status'       => 'active',
        'is_universal' => false,
    ];

    protected function casts(): array
    {
        return [
            'warranty_months' => 'integer',
            'is_universal'    => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $part) {
            if (empty($part->id)) {
                $part->id = (string) Str::uuid();
            }
            if (empty($part->slug)) {
                $part->slug = static::uniqueSlug($part->name);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'part';
        $slug = $base;
        $i = 1;
        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    // ─── Relationships ──────────────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function oemNumbers(): HasMany
    {
        return $this->hasMany(PartOemNumber::class);
    }

    public function guides(): HasMany
    {
        return $this->hasMany(PartGuide::class)->orderBy('sort_order');
    }

    public function media(): HasMany
    {
        return $this->hasMany(PartMedia::class)->orderByDesc('is_primary')->orderBy('sort_order');
    }

    public function alternatives(): HasMany
    {
        return $this->hasMany(PartAlternative::class);
    }

    public function fitments(): HasMany
    {
        return $this->hasMany(PartFitment::class);
    }

    /** Vendor offerings of this canonical part (existing products table). */
    public function offerings(): HasMany
    {
        return $this->hasMany(\App\Modules\Products\Models\Product::class, 'part_id');
    }

    // ─── Scopes / helpers ─────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * PM3: parts compatible with a vehicle selection — universal parts, or parts
     * with a fitment rule matching the selection.
     *
     * @param  array{make_id: string, model_id: string, year?: int|null, generation_id?: string|null, variant_id?: string|null, engine_id?: string|null, transmission_id?: string|null}  $selection
     */
    public function scopeCompatibleWith(Builder $query, array $selection): Builder
    {
        return $query->where(function ($q) use ($selection) {
            $q->where('is_universal', true)
              ->orWhereHas('fitments', fn ($f) => $f->matchingSelection($selection));
        });
    }

    /** PM3: does this part fit the given vehicle selection? */
    public function fitsSelection(array $selection): bool
    {
        if ($this->is_universal) {
            return true;
        }

        return $this->fitments()->matchingSelection($selection)->exists();
    }

    /**
     * PM10: active vehicle listings (for sale) this part fits — the part→vehicle
     * cross-sell direction. Universal parts don't surface a vehicle list.
     *
     * @return \Illuminate\Support\Collection<int, \App\Modules\Vehicles\Models\Vehicle>
     */
    public function compatibleVehicles(int $limit = 6): \Illuminate\Support\Collection
    {
        $fitments = $this->relationLoaded('fitments') ? $this->fitments : $this->fitments()->get();

        if ($this->is_universal || $fitments->isEmpty()) {
            return collect();
        }

        return \App\Modules\Vehicles\Models\Vehicle::query()
            ->active()
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(function ($outer) use ($fitments) {
                foreach ($fitments as $f) {
                    $outer->orWhere(function ($q) use ($f) {
                        $q->where('make_id', $f->make_id)->where('model_id', $f->model_id)
                          ->when($f->year_start, fn ($w) => $w->where('year', '>=', $f->year_start))
                          ->when($f->year_end, fn ($w) => $w->where('year', '<=', $f->year_end));
                    });
                }
            })
            ->with(['make', 'vehicleModel', 'images'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function primaryImage(): ?PartMedia
    {
        return $this->media->first();
    }

    /**
     * PM1: other active parts that share at least one OEM number — derivable
     * alternatives, complementing the manually-curated `alternatives` links.
     *
     * @return \Illuminate\Support\Collection<int, Part>
     */
    public function relatedByOem(int $limit = 6): \Illuminate\Support\Collection
    {
        $numbers = $this->oemNumbers()->pluck('number');

        if ($numbers->isEmpty()) {
            return collect();
        }

        return static::active()
            ->where('id', '!=', $this->id)
            ->whereHas('oemNumbers', fn ($q) => $q->whereIn('number', $numbers))
            ->limit($limit)
            ->get();
    }
}
