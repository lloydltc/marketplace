<?php

namespace App\Modules\Vehicles\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** PM0: a model's generation, e.g. Hilux "Revo" 2015–2024. */
class VehicleGeneration extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['model_id', 'name', 'year_start', 'year_end', 'is_active'];

    protected function casts(): array
    {
        return [
            'year_start' => 'integer',
            'year_end'   => 'integer',
            'is_active'  => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $g) {
            if (empty($g->id)) {
                $g->id = (string) Str::uuid();
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(VehicleVariant::class, 'generation_id');
    }
}
