<?php

namespace App\Modules\History\Adapters;

use App\Modules\Notifications\Models\ListingPriceHistory;
use App\Modules\Vehicles\Models\Vehicle;

/** HR2: ownership/listing history from SalmaDrive's own records (high confidence). */
class PlatformHistoryAdapter implements HistoryDataSourceAdapter
{
    public function assemble(Vehicle $vehicle): array
    {
        $priceChanges = ListingPriceHistory::where('subject_type', Vehicle::class)
            ->where('subject_id', $vehicle->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($h) => [
                'date'    => $h->created_at->toDateString(),
                'from'    => $h->currency . ' ' . number_format((float) $h->old_price, 2),
                'to'      => $h->currency . ' ' . number_format((float) $h->new_price, 2),
                'is_drop' => $h->is_drop,
            ])->all();

        $data = [
            'owner_type'        => $vehicle->isListedByVendor() ? 'Dealer' : 'Private seller',
            'first_listed'      => optional($vehicle->published_at ?? $vehicle->created_at)->toDateString(),
            'times_renewed'     => (int) ($vehicle->expiry_count ?? 0),
            'price_changes'     => $priceChanges,
            'currently_listed'  => $vehicle->isActive(),
        ];

        return [
            'type'         => 'ownership',
            'availability' => 'available',
            'data'         => $data,
            'confidence'   => 'high',
            'provenance'   => 'SalmaDrive platform records',
            'retrieved_at' => now(),
        ];
    }
}
