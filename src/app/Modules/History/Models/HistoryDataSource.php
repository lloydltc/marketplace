<?php

namespace App\Modules\History\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** HR1: a pluggable data source (adapter) feeding report sections. */
class HistoryDataSource extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'name', 'type', 'adapter', 'status', 'config'];

    protected function casts(): array
    {
        return ['config' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $s) {
            if (empty($s->id)) {
                $s->id = (string) Str::uuid();
            }
        });
    }

    /** Sources that can contribute data now (their own data or manual entry). */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->whereIn('status', ['live', 'manual']);
    }

    public function isUnavailable(): bool
    {
        return $this->status === 'unavailable';
    }
}
