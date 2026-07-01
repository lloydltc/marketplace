<?php

namespace App\Modules\Inspection\Models;

use App\Models\User;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** TI3/TI4: a paid vehicle inspection booking + its standardized report. */
class Inspection extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'buyer_id', 'inspector_id', 'vehicle_id', 'vehicle_ref', 'scheduled_for', 'status',
        'price_minor', 'currency', 'payment_reference', 'paid_at', 'report', 'verdict',
        'report_submitted_at', 'rating_given',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime', 'paid_at' => 'datetime', 'report_submitted_at' => 'datetime',
            'report' => 'array', 'price_minor' => 'integer', 'rating_given' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $i) {
            if (empty($i->id)) {
                $i->id = (string) Str::uuid();
            }
        });
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Inspector::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'in_progress', 'completed'], true);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function vehicleLabel(): string
    {
        return $this->vehicle?->displayTitle() ?? ($this->vehicle_ref ?: 'Vehicle');
    }

    public function priceUsd(): float
    {
        return $this->price_minor / 100;
    }
}
