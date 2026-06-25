<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            PlatformSettingsSeeder::class,
            PromotionPackageSeeder::class,
            SuperAdminSeeder::class,
            CategorySeeder::class,
            PartCategorySeeder::class,
            VehicleMakeSeeder::class,
            VehicleTaxonomySeeder::class,
            FeatureDefinitionSeeder::class,
        ]);
    }
}
