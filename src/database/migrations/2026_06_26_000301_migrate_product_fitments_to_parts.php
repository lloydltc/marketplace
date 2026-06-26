<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * PM3: lift H10 product-level fitments onto the canonical part for any product
     * already linked to a part (fitment authored once). H10's product_fitments and
     * its cross-sell scopes are LEFT INTACT as the fallback for products not yet
     * linked to a canonical part — nothing about H10 breaks.
     *
     * At rollout no products are linked yet, so this is typically a no-op; it's
     * written to be correct if/when links exist.
     */
    public function up(): void
    {
        $rows = DB::table('product_fitments as pf')
            ->join('products as p', 'p.id', '=', 'pf.product_id')
            ->whereNotNull('p.part_id')
            ->select('p.part_id', 'pf.make_id', 'pf.model_id', 'pf.year_from', 'pf.year_to')
            ->get();

        foreach ($rows as $row) {
            if ($row->make_id === null || $row->model_id === null) {
                continue; // canonical fitment anchors on make+model
            }

            $exists = DB::table('part_fitments')
                ->where('part_id', $row->part_id)
                ->where('make_id', $row->make_id)
                ->where('model_id', $row->model_id)
                ->where('year_start', $row->year_from)
                ->where('year_end', $row->year_to)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('part_fitments')->insert([
                'id'         => (string) Str::uuid(),
                'part_id'    => $row->part_id,
                'make_id'    => $row->make_id,
                'model_id'   => $row->model_id,
                'year_start' => $row->year_from,
                'year_end'   => $row->year_to,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Non-destructive forward migration; nothing to reverse.
    }
};
