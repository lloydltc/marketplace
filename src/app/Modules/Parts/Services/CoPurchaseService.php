<?php

namespace App\Modules\Parts\Services;

use App\Modules\Parts\Models\Part;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * PM5: "frequently bought together" via deterministic co-purchase counts — NO AI.
 * Counts how often other parts appear in the same orders as this part's offerings,
 * ranked by frequency.
 */
class CoPurchaseService
{
    /**
     * @return Collection<int, Part>
     */
    public function frequentlyBoughtWith(Part $part, int $limit = 4): Collection
    {
        $offeringIds = $part->offerings()->pluck('id');

        if ($offeringIds->isEmpty()) {
            return collect();
        }

        // Orders that contained one of this part's offerings.
        $orderIds = DB::table('order_items')
            ->whereIn('product_id', $offeringIds)
            ->distinct()
            ->pluck('order_id');

        if ($orderIds->isEmpty()) {
            return collect();
        }

        // Other parts purchased in those same orders, ranked by co-occurrence.
        $ranked = DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('order_items.order_id', $orderIds)
            ->whereNotNull('products.part_id')
            ->where('products.part_id', '!=', $part->id)
            ->groupBy('products.part_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit)
            ->pluck('products.part_id');

        if ($ranked->isEmpty()) {
            return collect();
        }

        // Hydrate, preserving rank order; only active parts.
        $parts = Part::active()->whereIn('id', $ranked)->with('media')->get()->keyBy('id');

        return $ranked->map(fn ($id) => $parts->get($id))->filter()->values();
    }
}
