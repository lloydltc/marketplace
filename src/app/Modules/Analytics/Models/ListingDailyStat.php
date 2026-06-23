<?php

namespace App\Modules\Analytics\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * H5: pre-aggregated daily counts per listing + event type. The seller/admin
 * dashboards read these (plus today's raw events) rather than scanning raw rows.
 */
class ListingDailyStat extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'subject_type', 'subject_id', 'seller_user_id', 'vendor_id',
        'stat_date', 'type', 'count',
    ];

    protected $casts = ['stat_date' => 'date', 'count' => 'integer'];

    protected static function booted(): void
    {
        static::creating(function (self $s) {
            if (empty($s->id)) {
                $s->id = (string) Str::uuid();
            }
        });
    }

    public function scopeForSeller(Builder $q, string $userId): Builder
    {
        return $q->where('seller_user_id', $userId);
    }

    public function scopeForVendor(Builder $q, string $vendorId): Builder
    {
        return $q->where('vendor_id', $vendorId);
    }
}
