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
        'name', 'key', 'type', 'unit', 'options',
        'is_filterable', 'group', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options'       => 'array',
            'is_filterable' => 'boolean',
            'is_active'     => 'boolean',
            'sort_order'    => 'integer',
        ];
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
