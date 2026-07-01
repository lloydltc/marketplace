<?php

namespace App\Modules\TradeIn\Services;

use App\Modules\Vehicles\Models\Vehicle;

/**
 * TI1: comparable-listing valuation (NO AI, no ML). Finds similar listings, takes
 * the median price, applies transparent config-driven mileage + condition
 * adjustments, and returns an estimate RANGE — explicitly "an estimate, not an
 * offer". Returns null when there aren't enough comparables to be honest.
 */
class ValuationService
{
    /**
     * @return array{low_minor: int, high_minor: int, base: float, comparables: int, currency: string}|null
     */
    public function estimate(string $makeId, string $modelId, int $year, int $mileage, string $condition): ?array
    {
        $band = (int) config('valuation.comparables_year_band', 2);

        $comparables = $this->comparables($makeId, $modelId, $year, $band);
        if ($comparables->count() < (int) config('valuation.min_comparables', 3)) {
            // Widen: same make+model, any year.
            $comparables = $this->comparables($makeId, $modelId, null, null);
        }
        if ($comparables->count() < (int) config('valuation.min_comparables', 3)) {
            return null; // not enough data to estimate honestly
        }

        $prices = $comparables->pluck('price_usd')->map(fn ($p) => (float) $p)->sort()->values();
        $base = $this->median($prices->all());

        // Mileage adjustment: above the comparable average lowers value, below raises it.
        $avgMileage = (float) $comparables->avg('mileage');
        $per10k = (float) config('valuation.mileage_adjust_per_10k', 0.03);
        $max = (float) config('valuation.max_mileage_adjust', 0.30);
        $mileageAdj = max(-$max, min($max, (($avgMileage - $mileage) / 10000) * $per10k));

        $conditionFactor = (float) (config('valuation.condition_factors.' . $condition) ?? 1.0);

        $adjusted = $base * (1 + $mileageAdj) * $conditionFactor;
        $spread = (float) config('valuation.range_spread', 0.10);

        return [
            'low_minor'   => (int) round($adjusted * (1 - $spread) * 100),
            'high_minor'  => (int) round($adjusted * (1 + $spread) * 100),
            'base'        => round($adjusted, 2),
            'comparables' => $comparables->count(),
            'currency'    => 'USD',
        ];
    }

    /** @return \Illuminate\Support\Collection<int, Vehicle> */
    private function comparables(string $makeId, string $modelId, ?int $year, ?int $band)
    {
        return Vehicle::query()
            ->where('make_id', $makeId)
            ->where('model_id', $modelId)
            ->whereNotNull('price_usd')
            ->whereIn('status', ['active', 'expired']) // real listings we've carried
            ->when($year !== null && $band !== null, fn ($q) => $q->whereBetween('year', [$year - $band, $year + $band]))
            ->get(['price_usd', 'mileage']);
    }

    /** @param list<float> $values */
    private function median(array $values): float
    {
        sort($values);
        $n = count($values);
        $mid = intdiv($n, 2);

        return $n % 2 ? $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
    }
}
