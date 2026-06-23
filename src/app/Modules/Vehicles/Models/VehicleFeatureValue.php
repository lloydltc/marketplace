<?php

namespace App\Modules\Vehicles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * D4: a vehicle's value for one feature definition. Stored as a string; read it
 * type-cast via {@see typed()} so booleans/numbers behave correctly in views.
 */
class VehicleFeatureValue extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['vehicle_id', 'feature_definition_id', 'value'];

    protected static function booted(): void
    {
        static::creating(function (self $v) {
            if (empty($v->id)) {
                $v->id = (string) Str::uuid();
            }
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(FeatureDefinition::class, 'feature_definition_id');
    }

    /** The value cast to its definition's type. */
    public function typed(): bool|int|float|string
    {
        return match ($this->definition?->type) {
            'boolean' => (bool) ((int) $this->value),
            'number'  => str_contains($this->value, '.') ? (float) $this->value : (int) $this->value,
            default   => (string) $this->value,
        };
    }

    /** Human display value for the buyer spec sheet. */
    public function display(): string
    {
        $def = $this->definition;

        if ($def?->type === 'boolean') {
            return ((int) $this->value) === 1 ? 'Yes' : 'No';
        }

        $unit = $def?->unit ? ' ' . $def->unit : '';

        return trim($this->value . $unit);
    }
}
