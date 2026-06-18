<?php

namespace Database\Seeders;

use App\Modules\Promotions\Models\PromotionPackage;
use Illuminate\Database\Seeder;

/**
 * Default dealer packages (placeholders — tune in the admin UI). Idempotent.
 */
class PromotionPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['Starter', 25, 10, 2, 2, 30],
            ['Dealer',  60, 30, 6, 6, 30],
            ['Pro',    120, 80, 15, 15, 30],
        ];

        foreach ($packages as [$name, $price, $listings, $features, $bumps, $days]) {
            PromotionPackage::firstOrCreate(
                ['name' => $name],
                [
                    'price'           => $price,
                    'currency'        => 'ZWL',
                    'listing_credits' => $listings,
                    'feature_credits' => $features,
                    'bump_credits'    => $bumps,
                    'duration_days'   => $days,
                    'is_active'       => true,
                ],
            );
        }
    }
}
