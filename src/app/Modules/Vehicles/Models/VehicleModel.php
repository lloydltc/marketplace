<?php

namespace App\Modules\Vehicles\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VehicleModel extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['make_id', 'name', 'slug', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'make_id');
    }

    public function generations(): HasMany
    {
        return $this->hasMany(VehicleGeneration::class, 'model_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(VehicleVariant::class, 'model_id');
    }
}
