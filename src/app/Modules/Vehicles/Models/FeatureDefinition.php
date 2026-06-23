<?php

namespace App\Modules\Vehicles\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * D4: an admin-defined vehicle feature/spec (e.g. "Parking Sensors", "Doors").
 * Type drives the seller input control and the buyer display, and `is_filterable`
 * exposes it as a D3 facet.
 */
class FeatureDefinition extends Model
{
    public const TYPES = ['boolean', 'number', 'enum', 'text'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name', 'key', 'type', 'unit', 'options', 'applies_to_types',
        'is_filterable', 'group', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options'          => 'array',
            'applies_to_types' => 'array',
            'is_filterable'    => 'boolean',
            'is_active'        => 'boolean',
            'sort_order'       => 'integer',
        ];
    }

    /** Does this feature apply to the given listing type? (null = all types). */
    public function appliesToType(?string $type): bool
    {
        return empty($this->applies_to_types) || in_array($type, $this->applies_to_types, true);
    }

    /** Scope: definitions applicable to a listing type (NULL applies_to_types = all). */
    public function scopeForType(Builder $q, ?string $type): Builder
    {
        if ($type === null) {
            return $q;
        }

        return $q->where(function ($w) use ($type) {
            $w->whereNull('applies_to_types')
              ->orWhereJsonContains('applies_to_types', $type);
        });
    }

    protected static function booted(): void
    {
        static::creating(function (self $def) {
            if (empty($def->id)) {
                $def->id = (string) Str::uuid();
            }
            if (empty($def->key)) {
                $def->key = Str::slug($def->name, '_');
            }
        });
    }

    public function values(): HasMany
    {
        return $this->hasMany(VehicleFeatureValue::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeFilterable(Builder $q): Builder
    {
        return $q->where('is_active', true)->where('is_filterable', true);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('group')->orderBy('sort_order')->orderBy('name');
    }
}
