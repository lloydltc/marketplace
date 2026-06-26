<?php

namespace App\Modules\Parts\Services;

use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Illuminate\Contracts\Session\Session;

/**
 * PM3: the buyer's currently-selected vehicle for fitment ("shop parts for my
 * Hilux"). Session-backed (works for guests); My Garage (PM7) writes into it too.
 */
class FitmentContext
{
    private const KEY = 'fitment.selection';

    public function __construct(private readonly Session $session) {}

    public function has(): bool
    {
        $sel = $this->get();

        return ! empty($sel['make_id']) && ! empty($sel['model_id']);
    }

    /**
     * @return array{make_id: ?string, model_id: ?string, year: ?int, generation_id: ?string, variant_id: ?string, engine_id: ?string, transmission_id: ?string, label: ?string}
     */
    public function get(): array
    {
        return array_merge([
            'make_id' => null, 'model_id' => null, 'year' => null,
            'generation_id' => null, 'variant_id' => null,
            'engine_id' => null, 'transmission_id' => null, 'label' => null,
        ], (array) $this->session->get(self::KEY, []));
    }

    /** Selection array shaped for the fitment match scope (no label). */
    public function selection(): array
    {
        $sel = $this->get();
        unset($sel['label']);

        return $sel;
    }

    public function set(array $selection): void
    {
        $selection['label'] = $this->buildLabel($selection);
        $this->session->put(self::KEY, $selection);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
    }

    private function buildLabel(array $selection): string
    {
        $make  = ! empty($selection['make_id']) ? optional(VehicleMake::find($selection['make_id']))->name : null;
        $model = ! empty($selection['model_id']) ? optional(VehicleModel::find($selection['model_id']))->name : null;

        return trim(($selection['year'] ?? '') . ' ' . trim(($make ?? '') . ' ' . ($model ?? ''))) ?: 'your vehicle';
    }
}
