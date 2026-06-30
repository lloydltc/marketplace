<?php

namespace App\Modules\History\Models;

use App\Models\User;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** HR1: an assembled vehicle history report. */
class HistoryReport extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vehicle_id', 'vin', 'plate', 'requested_by', 'status',
        'price_minor', 'currency', 'payment_reference', 'purchased_at', 'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'price_minor'  => 'integer',
            'purchased_at' => 'datetime',
            'refunded_at'  => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $r) {
            if (empty($r->id)) {
                $r->id = (string) Str::uuid();
            }
        });
    }

    public function sections(): HasMany
    {
        return $this->hasMany(HistoryReportSection::class, 'report_id')->orderBy('sort_order');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isPurchased(): bool
    {
        return $this->status === 'purchased';
    }

    public function priceUsd(): float
    {
        return $this->price_minor / 100;
    }
}
