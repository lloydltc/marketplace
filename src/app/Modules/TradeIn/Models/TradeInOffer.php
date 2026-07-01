<?php

namespace App\Modules\TradeIn\Models;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** TI2: a dealer's bid on a trade-in submission. */
class TradeInOffer extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['trade_in_id', 'vendor_id', 'amount_minor', 'currency', 'notes', 'status', 'expires_at'];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'expires_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $o) {
            if (empty($o->id)) {
                $o->id = (string) Str::uuid();
            }
        });
    }

    public function tradeIn(): BelongsTo
    {
        return $this->belongsTo(TradeIn::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function amount(): string
    {
        return $this->currency . ' ' . number_format($this->amount_minor / 100, 2);
    }
}
