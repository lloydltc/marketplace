<?php

namespace App\Modules\Parts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** PM1: an installation guide (doc or video link) attached to a canonical part. */
class PartGuide extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['part_id', 'title', 'type', 'url', 'content', 'sort_order'];

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
