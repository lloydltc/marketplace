<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * PM1: seed the parts category tree into the existing hierarchical `categories`
 * table (reuse, don't rebuild). Idempotent by slug.
 */
class PartCategorySeeder extends Seeder
{
    /** Top-level parts categories (PM1 spec). */
    private const CATEGORIES = [
        'Engine', 'Suspension', 'Brakes', 'Electrical', 'Body',
        'Tyres', 'Wheels', 'Accessories', 'Performance', 'Service Kits',
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $order => $name) {
            $slug = Str::slug($name);

            if (DB::table('categories')->where('slug', $slug)->exists()) {
                continue;
            }

            DB::table('categories')->insert([
                'id'         => (string) Str::uuid(),
                'parent_id'  => null,
                'name'       => $name,
                'slug'       => $slug,
                'sort_order' => 100 + $order, // keep below any existing product categories
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
