<?php

namespace App\Modules\Parts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** PM1: an OEM / aftermarket / cross-reference number for a canonical part. */
class PartOemNumber extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['part_id', 'number', 'type', 'brand'];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->id)) {
                $m->id = (string) Str::uuid();
            }
        });
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
