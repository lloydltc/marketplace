<?php

namespace Database\Seeders;

use App\Modules\Categories\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $roots = [
            [
                'name'        => 'Vehicles',
                'icon'        => '🚗',
                'description' => 'New and used cars, trucks, SUVs and motorcycles.',
                'sort_order'  => 1,
            ],
            [
                'name'        => 'Spare Parts',
                'icon'        => '🔧',
                'description' => 'Genuine and aftermarket replacement parts for all vehicle makes.',
                'sort_order'  => 2,
            ],
            [
                'name'        => 'Accessories',
                'icon'        => '🎁',
                'description' => 'Seat covers, mats, audio systems, and interior/exterior accessories.',
                'sort_order'  => 3,
            ],
            [
                'name'        => 'Tyres & Rims',
                'icon'        => '⚙️',
                'description' => 'Tyres, alloy rims, hubcaps and wheel accessories.',
                'sort_order'  => 4,
            ],
            [
                'name'        => 'Tools',
                'icon'        => '🛠️',
                'description' => 'Workshop tools, diagnostic equipment and garage essentials.',
                'sort_order'  => 5,
            ],
            [
                'name'        => 'Services',
                'icon'        => '🏪',
                'description' => 'Servicing kits, lubricants, and workshop service offerings.',
                'sort_order'  => 6,
            ],
        ];

        foreach ($roots as $rootData) {
            $root = Category::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($rootData['name'])],
                $rootData
            );
        }

        // Seed sub-categories under Vehicles
        $vehicles = Category::where('name', 'Vehicles')->first();
        if ($vehicles) {
            $vehicleSubs = [
                ['name' => 'Cars',          'icon' => '🚙', 'sort_order' => 1],
                ['name' => 'SUVs & 4x4',    'icon' => '🚐', 'sort_order' => 2],
                ['name' => 'Trucks',         'icon' => '🚛', 'sort_order' => 3],
                ['name' => 'Motorcycles',    'icon' => '🏍️', 'sort_order' => 4],
                ['name' => 'Buses & Minibuses', 'icon' => '🚌', 'sort_order' => 5],
            ];

            foreach ($vehicleSubs as $sub) {
                Category::firstOrCreate(
                    ['slug' => \Illuminate\Support\Str::slug($sub['name'])],
                    array_merge($sub, ['parent_id' => $vehicles->id])
                );
            }
        }

        // Seed sub-categories under Spare Parts
        $parts = Category::where('name', 'Spare Parts')->first();
        if ($parts) {
            $partSubs = [
                ['name' => 'Engine Parts',   'icon' => '⚙️', 'sort_order' => 1],
                ['name' => 'Brakes',         'icon' => '🛑', 'sort_order' => 2],
                ['name' => 'Suspension',     'icon' => '🔩', 'sort_order' => 3],
                ['name' => 'Electrical',     'icon' => '⚡', 'sort_order' => 4],
                ['name' => 'Body Parts',     'icon' => '🚪', 'sort_order' => 5],
                ['name' => 'Oils & Fluids',  'icon' => '🛢️', 'sort_order' => 6],
            ];

            foreach ($partSubs as $sub) {
                Category::firstOrCreate(
                    ['slug' => \Illuminate\Support\Str::slug($sub['name'])],
                    array_merge($sub, ['parent_id' => $parts->id])
                );
            }
        }
    }
}
