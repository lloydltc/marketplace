<?php

namespace App\Modules\History\Adapters;

use App\Modules\Vehicles\Models\Vehicle;

/**
 * HR2: dealer-supplied service history. Manual — records are entered by the
 * dealer/admin (HR4). The adapter seeds an honest empty section; manual entries
 * are merged in by the admin tooling and preserved across re-assembly.
 */
class ServiceHistoryAdapter implements HistoryDataSourceAdapter
{
    public function assemble(Vehicle $vehicle): array
    {
        return [
            'type'         => 'service',
            'availability' => 'manual',
            'data'         => ['records' => []], // filled via admin manual entry
            'confidence'   => 'medium',
            'provenance'   => 'Dealer-supplied (manual entry)',
            'retrieved_at' => now(),
        ];
    }
}
