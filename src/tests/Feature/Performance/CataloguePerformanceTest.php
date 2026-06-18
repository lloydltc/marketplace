<?php

namespace Tests\Feature\Performance;

use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Products\Models\Product;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * P5: lock in N+1-free catalogue rendering. The query count must stay bounded and
 * NOT grow with the number of listings (proves relations are eager-loaded). These
 * assertions fail loudly if someone removes the eager loads later.
 */
class CataloguePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    public function test_products_index_query_count_is_bounded(): void
    {
        // 30 products across distinct vendors + categories — an N+1 on either
        // relation would add ~30 queries and blow past the bound.
        for ($i = 0; $i < 30; $i++) {
            $vendor = Vendor::create([
                'name' => 'V' . $i, 'slug' => 'v-' . Str::random(6),
                'contact_email' => "v{$i}@x.com", 'status' => 'approved',
            ]);
            $cat = Category::create(['name' => 'C' . $i, 'slug' => 'c-' . Str::random(6), 'sort_order' => 0]);
            Product::create([
                'vendor_id' => $vendor->id, 'category_id' => $cat->id,
                'title' => "Item {$i} name", 'description' => 'x',
                'price_zwl' => 100, 'quantity' => 5, 'status' => 'active',
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('products.index'))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(20, $count, "Products index ran {$count} queries — likely an N+1 regression.");
    }

    public function test_vehicles_index_query_count_is_bounded(): void
    {
        $make  = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $model = VehicleModel::create(['make_id' => $make->id, 'name' => 'Corolla', 'slug' => 'corolla-' . Str::random(4)]);

        for ($i = 0; $i < 30; $i++) {
            $vendor = Vendor::create([
                'name' => 'VV' . $i, 'slug' => 'vv-' . Str::random(6),
                'contact_email' => "vv{$i}@x.com", 'status' => 'approved',
            ]);
            Vehicle::create([
                'vendor_id' => $vendor->id, 'make_id' => $make->id, 'model_id' => $model->id,
                'year' => 2020, 'body_type' => 'sedan', 'transmission' => 'manual',
                'fuel_type' => 'petrol', 'mileage' => 1000, 'color' => 'blue',
                'condition' => 'used', 'price_zwl' => 5000000, 'status' => 'active',
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('vehicles.index'))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(20, $count, "Vehicles index ran {$count} queries — likely an N+1 regression.");
    }
}
