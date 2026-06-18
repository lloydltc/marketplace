<?php

namespace App\Modules\Delivery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeliveryZone extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['name', 'flat_fee', 'is_active'];

    protected function casts(): array
    {
        return [
            'flat_fee'  => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $zone) {
            if (empty($zone->id)) {
                $zone->id = (string) Str::uuid();
            }
        });
    }
}
