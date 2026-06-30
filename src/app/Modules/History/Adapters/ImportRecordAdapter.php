<?php

namespace App\Modules\History\Adapters;

use App\Modules\Vehicles\Models\Vehicle;

/** HR2: import/registration-adjacent facts from the listing's ZW fields + VIN. */
class ImportRecordAdapter implements HistoryDataSourceAdapter
{
    public function assemble(Vehicle $vehicle): array
    {
        $data = array_filter([
            'vin'            => $vehicle->vin,
            'recent_import'  => $vehicle->is_recent_import ? 'Yes' : null,
            'duty_paid'      => $vehicle->duty_paid === null ? null : ($vehicle->duty_paid ? 'Yes' : 'No'),
            'steering'       => $vehicle->steering ? strtoupper($vehicle->steering) : null,
            'ref_code'       => $vehicle->ref_code,
        ], fn ($v) => $v !== null && $v !== '');

        return [
            'type'         => 'import',
            'availability' => $data === [] ? 'unavailable' : 'available',
            'data'         => $data ?: null,
            'confidence'   => 'medium',
            'provenance'   => 'Seller-declared import details',
            'retrieved_at' => now(),
        ];
    }
}
