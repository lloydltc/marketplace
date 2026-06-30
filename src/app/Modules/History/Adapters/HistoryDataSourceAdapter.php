<?php

namespace App\Modules\History\Adapters;

use App\Modules\Vehicles\Models\Vehicle;

/**
 * HR2: a pluggable history data source. Returns one section's worth of data for a
 * vehicle, always with provenance + confidence + retrieved_at. Returning an
 * "unavailable" section is valid and honest — never fabricate.
 */
interface HistoryDataSourceAdapter
{
    /**
     * @return array{type: string, availability: string, data: ?array, confidence: string, provenance: string, retrieved_at: \DateTimeInterface}
     */
    public function assemble(Vehicle $vehicle): array;
}
