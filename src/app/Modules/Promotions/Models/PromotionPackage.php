<?php

namespace App\Modules\Promotions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PromotionPackage extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'price',
        'currency',
        'listing_credits',
        'feature_credits',
        'bump_credits',
        'duration_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price'           => 'decimal:2',
            'listing_credits' => 'integer',
            'feature_credits' => 'integer',
            'bump_credits'    => 'integer',
            'duration_days'   => 'integer',
            'is_active'       => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $package) {
            if (empty($package->id)) {
                $package->id = (string) Str::uuid();
            }
        });
    }
}
