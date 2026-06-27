<?php

namespace App\Modules\Parts\Models;

use App\Models\User;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use App\Modules\Vehicles\Models\VehicleVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** PM7: a vehicle a buyer saved to their garage. */
class GarageVehicle extends Model
{
    protected $table = 'user_garage_vehicles';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'make_id', 'model_id', 'year',
        'variant_id', 'engine_id', 'transmission_id', 'nickname', 'is_default',
    ];

    protected function casts(): array
    {
        return ['year' => 'integer', 'is_default' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $g) {
            if (empty($g->id)) {
                $g->id = (string) Str::uuid();
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

    public function variant(): BelongsTo
    {
        return $this->belongsTo(VehicleVariant::class, 'variant_id');
    }

    /** Selection array shaped for the fitment context/match. */
    public function toSelection(): array
    {
        return [
            'make_id'         => $this->make_id,
            'model_id'        => $this->model_id,
            'year'            => $this->year,
            'variant_id'      => $this->variant_id,
            'engine_id'       => $this->engine_id,
            'transmission_id' => $this->transmission_id,
        ];
    }

    public function label(): string
    {
        if ($this->nickname) {
            return $this->nickname;
        }

        return trim(($this->year ? $this->year . ' ' : '') . ($this->make?->name ?? '') . ' ' . ($this->vehicleModel?->name ?? '')) ?: 'My vehicle';
    }
}
