<?php

namespace Tests\Feature\Discovery;

use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Media\Models\ProductImage;
use App\Modules\Media\Models\VehicleImage;
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
 * D1: real listing images must render on the landing + catalogue (not a masked
 * placeholder), the fallback shows only for genuinely image-less listings, and
 * the image query is eager-loaded (no N+1).
 */
class ImageRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function vendor(): Vendor
    {
        return Vendor::create([
            'name' => 'V ' . Str::random(4), 'slug' => 'v-' . Str::random(6),
            'contact_email' => 'v@x.com', 'status' => 'approved',
        ]);
    }

    private function product(Vendor $v, bool $withImage): Product
    {
        $p = Product::create([
            'vendor_id' => $v->id,
            'category_id' => Category::create(['name' => 'C', 'slug' => 'c-' . Str::random(6), 'sort_order' => 0])->id,
            'title' => 'Part ' . Str::random(4), 'description' => 'x',
            'price_usd' => 50, 'exchange_rate' => 36.5, 'price_zwl' => 1825,
            'quantity' => 5, 'status' => 'active',
        ]);
        if ($withImage) {
            ProductImage::create([
                'product_id' => $p->id, 'disk' => 'public',
                'original_path' => "products/{$p->id}/cover.jpg",
                'thumb_path' => "products/{$p->id}/cover_thumb.jpg",
                'medium_path' => "products/{$p->id}/cover_medium.jpg",
                'display_order' => 0, 'processed_at' => now(),
            ]);
        }

        return $p;
    }

    private function vehicle(Vendor $v, bool $withImage): Vehicle
    {
        $make  = VehicleMake::create(['name' => 'M' . Str::random(3), 'slug' => 'm-' . Str::random(6), 'sort_order' => 0]);
        $model = VehicleModel::create(['make_id' => $make->id, 'name' => 'X', 'slug' => 'x-' . Str::random(6)]);
        $veh = Vehicle::create([
            'vendor_id' => $v->id, 'make_id' => $make->id, 'model_id' => $model->id,
            'year' => 2020, 'body_type' => 'sedan', 'transmission' => 'manual',
            'fuel_type' => 'petrol', 'mileage' => 1000, 'color' => 'blue',
            'condition' => 'used', 'price_usd' => 9000, 'status' => 'active',
        ]);
        if ($withImage) {
            VehicleImage::create([
                'vehicle_id' => $veh->id, 'disk' => 'public',
                'original_path' => "vehicles/{$veh->id}/cover.jpg",
                'thumb_path' => "vehicles/{$veh->id}/cover_thumb.jpg",
                'medium_path' => "vehicles/{$veh->id}/cover_medium.jpg",
                'display_order' => 0, 'processed_at' => now(),
            ]);
        }

        return $veh;
    }

    public function test_landing_renders_real_images(): void
    {
        $v = $this->vendor();
        $product = $this->product($v, true);
        $vehicle = $this->vehicle($v, true);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee("products/{$product->id}/cover_thumb.jpg", false)
            ->assertSee("vehicles/{$vehicle->id}/cover_thumb.jpg", false);
    }

    public function test_catalogue_renders_real_images(): void
    {
        $v = $this->vendor();
        $product = $this->product($v, true);
        $vehicle = $this->vehicle($v, true);

        $this->get(route('products.index'))->assertOk()
            ->assertSee("products/{$product->id}/cover_thumb.jpg", false);
        $this->get(route('vehicles.index'))->assertOk()
            ->assertSee("vehicles/{$vehicle->id}/cover_thumb.jpg", false);
    }

    public function test_imageless_listing_shows_fallback_not_broken_image(): void
    {
        $v = $this->vendor();
        $product = $this->product($v, false);

        $res = $this->get(route('products.index'))->assertOk();
        // No image path renders for this product (its detail href contains the id,
        // so we check specifically for an image file path), and the fallback shows.
        $res->assertDontSee("products/{$product->id}/", false);
        $res->assertSee('🔧', false);
    }

    public function test_vehicle_catalogue_image_query_is_bounded(): void
    {
        $v = $this->vendor();
        for ($i = 0; $i < 12; $i++) {
            $this->vehicle($v, true);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('vehicles.index'))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Eager-loaded: images add ONE query, not one per vehicle.
        $this->assertLessThan(20, $count, "Vehicles index ran {$count} queries — image N+1 regression.");
    }
}
