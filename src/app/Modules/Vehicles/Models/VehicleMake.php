<?php

namespace App\Modules\Vehicles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VehicleMake extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['name', 'slug', 'sort_order'];

    protected static function booted(): void
    {
        static::creating(function (self $make) {
            if (empty($make->id)) {
                $make->id = (string) Str::uuid();
            }
        });
    }

    public function models(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'make_id')->orderBy('name');
    }
}
