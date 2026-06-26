<?php

namespace App\Modules\Parts\Models;

use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleGeneration;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use App\Modules\Vehicles\Models\VehicleVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * PM3: one canonical fitment rule. make/model anchor it; generation/variant/
 * engine/transmission are nullable (NULL = applies to all); year range bounds it
 * (NULL = unbounded).
 */
class PartFitment extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'part_id', 'make_id', 'model_id', 'generation_id', 'variant_id',
        'engine_id', 'transmission_id', 'year_start', 'year_end', 'notes',
    ];

    protected function casts(): array
    {
        return ['year_start' => 'integer', 'year_end' => 'integer'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $f) {
            if (empty($f->id)) {
                $f->id = (string) Str::uuid();
            }
        });
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'make_id');
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    /**
     * Constrain a fitment query to rows matching a concrete vehicle selection.
     * A null selection dimension matches only fitment rows that are also null on
     * that dimension (the buyer hasn't specified it, so we can't claim a narrower
     * fitment fits) — except that an unspecified selection still matches a NULL
     * (applies-to-all) fitment, which is the common case.
     *
     * @param  array{make_id: string, model_id: string, year?: int|null, generation_id?: string|null, variant_id?: string|null, engine_id?: string|null, transmission_id?: string|null}  $v
     */
    public function scopeMatchingSelection(Builder $query, array $v): Builder
    {
        $query->where('make_id', $v['make_id'])->where('model_id', $v['model_id']);

        if (! empty($v['year'])) {
            $year = (int) $v['year'];
            $query->where(fn ($q) => $q->whereNull('year_start')->orWhere('year_start', '<=', $year))
                  ->where(fn ($q) => $q->whereNull('year_end')->orWhere('year_end', '>=', $year));
        }

        // Each optional dimension: a NULL fitment applies to all; a set fitment must
        // equal the selection (when the buyer specified it).
        foreach (['generation_id', 'variant_id', 'engine_id', 'transmission_id'] as $dim) {
            $query->where(function ($q) use ($dim, $v) {
                $q->whereNull($dim);
                if (! empty($v[$dim])) {
                    $q->orWhere($dim, $v[$dim]);
                }
            });
        }

        return $query;
    }

    /** Human-readable fitment, e.g. "2015–2024 Toyota Hilux". */
    public function label(): string
    {
        $vehicle = trim(($this->make?->name ?? '') . ' ' . ($this->vehicleModel?->name ?? ''));

        $years = match (true) {
            $this->year_start && $this->year_end => "{$this->year_start}–{$this->year_end} ",
            (bool) $this->year_start             => "{$this->year_start}+ ",
            (bool) $this->year_end               => "up to {$this->year_end} ",
            default                              => '',
        };

        return trim($years . $vehicle) ?: 'All vehicles';
    }
}
