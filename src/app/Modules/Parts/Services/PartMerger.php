<?php

namespace App\Modules\Parts\Services;

use App\Modules\Parts\Models\Part;
use App\Modules\Parts\Models\PartFitment;
use App\Modules\Parts\Models\PartOemNumber;
use App\Modules\Products\Models\Product;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * PM9: merge a duplicate canonical part into a keeper. Offerings (products),
 * OEM numbers and fitments move to the keeper; the duplicate is soft-deleted.
 * Idempotent-safe within a transaction.
 */
class PartMerger
{
    public function merge(Part $keeper, Part $duplicate): void
    {
        if ($keeper->id === $duplicate->id) {
            throw new RuntimeException('Cannot merge a part into itself.');
        }

        DB::transaction(function () use ($keeper, $duplicate) {
            // Re-point vendor offerings.
            Product::where('part_id', $duplicate->id)->update(['part_id' => $keeper->id]);

            // Move OEM numbers, skipping ones the keeper already has.
            $keeperNumbers = $keeper->oemNumbers()->pluck('number')->map(fn ($n) => strtolower($n))->all();
            foreach ($duplicate->oemNumbers as $oem) {
                if (in_array(strtolower($oem->number), $keeperNumbers, true)) {
                    $oem->delete();
                    continue;
                }
                PartOemNumber::whereKey($oem->id)->update(['part_id' => $keeper->id]);
            }

            // Move fitment rows.
            PartFitment::where('part_id', $duplicate->id)->update(['part_id' => $keeper->id]);

            // Drop the duplicate's alternative links, then soft-delete it.
            $duplicate->alternatives()->delete();
            $duplicate->update(['status' => 'inactive']);
            $duplicate->delete();
        });
    }
}
