<?php

namespace App\Modules\TradeIn\Models;

use App\Models\User;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** TI1: a buyer's trade-in submission with a comparable-listing valuation range. */
class TradeIn extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'make_id', 'model_id', 'generation_id', 'year', 'mileage', 'condition', 'notes',
        'estimate_low_minor', 'estimate_high_minor', 'comparables_count', 'currency', 'status', 'accepted_offer_id',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer', 'mileage' => 'integer',
            'estimate_low_minor' => 'integer', 'estimate_high_minor' => 'integer', 'comparables_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $t) {
            if (empty($t->id)) {
                $t->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'make_id');
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(TradeInPhoto::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(TradeInOffer::class)->orderByDesc('amount_minor');
    }

    public function title(): string
    {
        return "{$this->year} " . ($this->make?->name ?? '') . ' ' . ($this->vehicleModel?->name ?? '');
    }

    public function hasEstimate(): bool
    {
        return $this->estimate_low_minor !== null && $this->estimate_high_minor !== null;
    }

    public function estimateRange(): ?string
    {
        return $this->hasEstimate()
            ? $this->currency . ' ' . number_format($this->estimate_low_minor / 100) . ' – ' . number_format($this->estimate_high_minor / 100)
            : null;
    }
}
