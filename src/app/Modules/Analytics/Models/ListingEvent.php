<?php

namespace App\Modules\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * H5: a single (deduped, bot-filtered) analytics event on a listing. Raw events
 * are pruned after aggregation into {@see ListingDailyStat}.
 */
class ListingEvent extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public const UPDATED_AT = null;

    protected $fillable = [
        'subject_type', 'subject_id', 'seller_user_id', 'vendor_id',
        'type', 'visitor_hash', 'occurred_on',
    ];

    protected $casts = ['occurred_on' => 'date'];

    protected static function booted(): void
    {
        static::creating(function (self $e) {
            if (empty($e->id)) {
                $e->id = (string) Str::uuid();
            }
        });
    }
}
