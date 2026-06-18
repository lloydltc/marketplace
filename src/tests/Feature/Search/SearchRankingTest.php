<?php

namespace Tests\Feature\Search;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Repositories\ProductRepositoryInterface;
use App\Modules\Settings\Services\SettingsService;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use App\Modules\Vehicles\Repositories\VehicleRepositoryInterface;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SearchRankingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function settings(): SettingsService
    {
        return app(SettingsService::class);
    }

    // ─── Products: FBS placement boost ──────────────────────────────────────────

    private function makeProductPair(): array
    {
        $vendor = Vendor::create([
            'name' => 'Shop', 'slug' => 'shop-' . Str::random(4),
            'contact_email' => 's@x.com', 'status' => 'approved',
        ]);
        $category = Category::create(['name' => 'Parts', 'slug' => 'parts-' . Str::random(4), 'sort_order' => 0]);

        // FBS product is OLDER, so recency alone would rank it second.
        $fbs = Product::create([
            'vendor_id' => $vendor->id, 'category_id' => $category->id,
            'title' => 'FBS Widget', 'description' => 'desc', 'price_zwl' => 100, 'quantity' => 5,
            'status' => 'active', 'fulfilment_type' => 'fbs',
        ]);
        Product::where('id', $fbs->id)->update(['created_at' => now()->subDay()]);

        $vendorOnly = Product::create([
            'vendor_id' => $vendor->id, 'category_id' => $category->id,
            'title' => 'Vendor Widget', 'description' => 'desc', 'price_zwl' => 100, 'quantity' => 5,
            'status' => 'active', 'fulfilment_type' => 'vendor',
        ]);
        Product::where('id', $vendorOnly->id)->update(['created_at' => now()]);

        return [$fbs->id, $vendorOnly->id];
    }

    public function test_fbs_boost_lifts_fbs_products_above_newer_vendor_products(): void
    {
        [$fbsId] = $this->makeProductPair();
        $this->settings()->set('search.fbs_placement_boost', 100);

        $results = app(ProductRepositoryInterface::class)->paginatePublic([]);

        $this->assertSame($fbsId, $results->first()->id);
    }

    public function test_fbs_boost_of_zero_falls_back_to_recency(): void
    {
        [, $vendorId] = $this->makeProductPair();
        $this->settings()->set('search.fbs_placement_boost', 0);

        $results = app(ProductRepositoryInterface::class)->paginatePublic([]);

        // With no boost, the newer vendor-fulfilled product ranks first.
        $this->assertSame($vendorId, $results->first()->id);
    }

    public function test_product_fulfilment_filter_restricts_results(): void
    {
        [$fbsId] = $this->makeProductPair();

        $results = app(ProductRepositoryInterface::class)->paginatePublic(['fulfilment' => 'fbs']);

        $this->assertCount(1, $results);
        $this->assertSame($fbsId, $results->first()->id);
    }

    // ─── Vehicles: featured priority ────────────────────────────────────────────

    private function makeVehiclePair(): array
    {
        $seller = User::factory()->create(['role' => 'private_seller']);
        $make   = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $model  = VehicleModel::create(['make_id' => $make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);

        $base = [
            'user_id' => $seller->id, 'make_id' => $make->id, 'model_id' => $model->id,
            'year' => 2020, 'body_type' => 'pickup', 'transmission' => 'manual', 'fuel_type' => 'diesel',
            'mileage' => 1000, 'color' => 'white', 'condition' => 'used', 'status' => 'active', 'price_zwl' => 100,
        ];

        // Featured vehicle is OLDER.
        $featured = Vehicle::create(array_merge($base, ['featured_until' => now()->addDays(7)]));
        Vehicle::where('id', $featured->id)->update(['created_at' => now()->subDay()]);

        $plain = Vehicle::create($base);
        Vehicle::where('id', $plain->id)->update(['created_at' => now()]);

        return [$featured->id, $plain->id];
    }

    public function test_featured_boost_lifts_featured_vehicles_first(): void
    {
        [$featuredId] = $this->makeVehiclePair();
        $this->settings()->set('search.featured_vehicle_boost', 100);

        $results = app(VehicleRepositoryInterface::class)->paginatePublic([]);

        $this->assertSame($featuredId, $results->first()->id);
    }

    public function test_featured_boost_of_zero_falls_back_to_recency(): void
    {
        [, $plainId] = $this->makeVehiclePair();
        $this->settings()->set('search.featured_vehicle_boost', 0);

        $results = app(VehicleRepositoryInterface::class)->paginatePublic([]);

        $this->assertSame($plainId, $results->first()->id);
    }
}
