<?php

namespace App\Modules\History\Adapters;

use App\Modules\Vehicles\Models\Vehicle;

/**
 * HR2: odometer reading — seller-declared only (independent verification is a
 * future data partnership). Low confidence, stated honestly.
 */
class OdometerAdapter implements HistoryDataSourceAdapter
{
    public function assemble(Vehicle $vehicle): array
    {
        return [
            'type'         => 'odometer',
            'availability' => $vehicle->mileage !== null ? 'available' : 'unavailable',
            'data'         => $vehicle->mileage !== null ? [
                'reading_km' => (int) $vehicle->mileage,
                'verified'   => false,
                'note'       => 'Seller-declared; not independently verified.',
            ] : null,
            'confidence'   => 'low',
            'provenance'   => 'Seller-declared',
            'retrieved_at' => now(),
        ];
    }
}
