<?php

namespace App\Modules\History\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * HR1: one section of a report from a single data source, carrying its own
 * provenance/confidence so the buyer always sees where each fact came from.
 */
class HistoryReportSection extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'report_id', 'source', 'type', 'availability', 'data',
        'confidence', 'provenance', 'retrieved_at', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['data' => 'array', 'retrieved_at' => 'datetime', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $s) {
            if (empty($s->id)) {
                $s->id = (string) Str::uuid();
            }
        });
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(HistoryReport::class, 'report_id');
    }

    public function isAvailable(): bool
    {
        return $this->availability === 'available' || $this->availability === 'manual';
    }
}
