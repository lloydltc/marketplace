<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Products\Models\Product;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    public function test_guest_can_view_landing_without_authenticating(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_landing_shows_active_products_and_vehicles_to_guests(): void
    {
        $vendor   = Vendor::create(['name' => 'Dealer Co', 'slug' => 'dealer-' . Str::random(4), 'contact_email' => 'd@x.com', 'status' => 'approved']);
        $category = Category::create(['name' => 'Parts', 'slug' => 'parts-' . Str::random(4), 'sort_order' => 0]);

        Product::create([
            'vendor_id' => $vendor->id, 'category_id' => $category->id,
            'title' => 'Landing Brake Disc', 'description' => 'desc', 'price_zwl' => 100,
            'quantity' => 5, 'status' => 'active',
        ]);

        $seller = User::factory()->create(['role' => 'private_seller']);
        $make   = VehicleMake::create(['name' => 'Mazda', 'slug' => 'mazda-' . Str::random(4), 'sort_order' => 0]);
        $model  = VehicleModel::create(['make_id' => $make->id, 'name' => 'Demio', 'slug' => 'demio-' . Str::random(4)]);
        Vehicle::create([
            'user_id' => $seller->id, 'make_id' => $make->id, 'model_id' => $model->id,
            'year' => 2019, 'body_type' => 'hatchback', 'transmission' => 'automatic', 'fuel_type' => 'petrol',
            'mileage' => 40000, 'color' => 'red', 'condition' => 'used', 'status' => 'active', 'price_zwl' => 5000,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Landing Brake Disc')
            ->assertSee('Mazda Demio');
    }
}
