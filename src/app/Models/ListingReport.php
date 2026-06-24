<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * H11: a report (buyer-submitted or auto-flagged) against a listing, feeding the
 * admin moderation queue.
 */
class ListingReport extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'reportable_type',
        'reportable_id',
        'reporter_user_id',
        'reporter_ip',
        'source',
        'reason',
        'note',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $report) {
            if (empty($report->id)) {
                $report->id = (string) Str::uuid();
            }
        });
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isAuto(): bool
    {
        return $this->source === 'auto';
    }

    /** Human label for the reason (config-driven, falls back to the raw key). */
    public function reasonLabel(): string
    {
        return config("moderation.reasons.{$this->reason}", Str::headline($this->reason));
    }
}
