<?php

namespace App\Modules\Parts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** PM1: a link from one canonical part to an alternative/substitute/upgrade part. */
class PartAlternative extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['part_id', 'alternative_part_id', 'relation'];

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
        return $this->belongsTo(Part::class, 'part_id');
    }

    public function alternative(): BelongsTo
    {
        return $this->belongsTo(Part::class, 'alternative_part_id');
    }
}
