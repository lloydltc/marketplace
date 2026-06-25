<?php

namespace App\Modules\Parts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** PM1: a catalog image for a canonical part (secure upload pipeline wired in PM9). */
class PartMedia extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['part_id', 'disk', 'path', 'is_primary', 'sort_order'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

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

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
