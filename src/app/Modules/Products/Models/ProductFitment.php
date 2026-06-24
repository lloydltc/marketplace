<?php

namespace App\Modules\Products\Models;

use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * H10: a single compatibility rule on a part. Null make/model/year = "any" on
 * that axis.
 */
class ProductFitment extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'product_id',
        'make_id',
        'model_id',
        'year_from',
        'year_to',
    ];

    protected function casts(): array
    {
        return [
            'year_from' => 'integer',
            'year_to'   => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $fitment) {
            if (empty($fitment->id)) {
                $fitment->id = (string) Str::uuid();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'make_id');
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    /** Constrain a fitment query to those matching a concrete vehicle. */
    public function scopeMatchingVehicle(Builder $query, Vehicle $vehicle): Builder
    {
        return $query
            ->where(fn ($q) => $q->whereNull('make_id')->orWhere('make_id', $vehicle->make_id))
            ->where(fn ($q) => $q->whereNull('model_id')->orWhere('model_id', $vehicle->model_id))
            ->where(fn ($q) => $q->whereNull('year_from')->orWhere('year_from', '<=', $vehicle->year))
            ->where(fn ($q) => $q->whereNull('year_to')->orWhere('year_to', '>=', $vehicle->year));
    }

    /** Human-readable fitment, e.g. "2015–2018 Toyota Hilux" or "Any Toyota". */
    public function label(): string
    {
        $make  = $this->make?->name;
        $model = $this->vehicleModel?->name;

        $vehicle = trim(($make ?? 'Any make') . ' ' . ($model ?? ($make ? '(all models)' : '')));

        $years = match (true) {
            $this->year_from && $this->year_to => "{$this->year_from}–{$this->year_to} ",
            (bool) $this->year_from            => "{$this->year_from}+ ",
            (bool) $this->year_to              => "up to {$this->year_to} ",
            default                            => '',
        };

        return trim($years . $vehicle);
    }
}
