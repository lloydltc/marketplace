<?php

namespace App\Modules\Vehicles\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** PM0: an engine lookup, e.g. "2.8 GD-6" / 2.8L / Diesel. */
class VehicleEngine extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['code', 'displacement', 'fuel_type', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $e) {
            if (empty($e->id)) {
                $e->id = (string) Str::uuid();
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
