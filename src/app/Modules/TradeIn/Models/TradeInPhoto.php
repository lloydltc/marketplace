<?php

namespace App\Modules\TradeIn\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** TI1: a photo attached to a trade-in submission. */
class TradeInPhoto extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['trade_in_id', 'disk', 'path'];

    protected static function booted(): void
    {
        static::creating(function (self $p) {
            if (empty($p->id)) {
                $p->id = (string) Str::uuid();
            }
        });
    }

    public function tradeIn(): BelongsTo
    {
        return $this->belongsTo(TradeIn::class);
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
