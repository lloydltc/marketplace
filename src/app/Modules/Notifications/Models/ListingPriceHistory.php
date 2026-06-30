<?php

namespace App\Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/** AC2: a recorded price change on a listing (drives price-drop alerts). */
class ListingPriceHistory extends Model
{
    protected $table = 'listing_price_history';

    public $incrementing = false;

    protected $keyType = 'string';

    public const UPDATED_AT = null;

    protected $fillable = [
        'subject_type', 'subject_id', 'old_price', 'new_price', 'currency', 'is_drop', 'alerted_at',
    ];

    protected function casts(): array
    {
        return [
            'old_price'  => 'decimal:2',
            'new_price'  => 'decimal:2',
            'is_drop'    => 'boolean',
            'alerted_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $h) {
            if (empty($h->id)) {
                $h->id = (string) Str::uuid();
            }
            if (empty($h->created_at)) {
                $h->created_at = now();
            }
        });
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
