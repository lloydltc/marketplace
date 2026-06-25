<?php

namespace App\Modules\Vehicles\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** PM0: a transmission lookup — manual / automatic / cvt / dct. */
class VehicleTransmission extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['type', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $t) {
            if (empty($t->id)) {
                $t->id = (string) Str::uuid();
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
