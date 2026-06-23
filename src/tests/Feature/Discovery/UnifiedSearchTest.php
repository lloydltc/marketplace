<?php

namespace Tests\Feature\Discovery;

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

/**
 * D2: one landing search returns BOTH vehicles and parts, sectioned, with a
 * useful RFQ empty state when nothing matches.
 */
class UnifiedSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);

        $vendor = Vendor::create([
            'name' => 'Dealer', 'slug' => 'dealer-' . Str::random(5),
            'contact_email' => 'd@x.com', 'status' => 'approved',
        ]);
        $cat = Category::create(['name' => 'Brakes', 'slug' => 'brakes-' . Str::random(5), 'sort_order' => 0]);
        Product::create([
            'vendor_id' => $vendor->id, 'category_id' => $cat->id,
            'title' => 'Toyota Brake Pad Set', 'description' => 'OEM brake pads',
            'price_usd' => 50, 'exchange_rate' => 36.5, 'price_zwl' => 1825,
            'quantity' => 5, 'status' => 'active',
        ]);
        $make = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $model = VehicleModel::create(['make_id' => $make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
        Vehicle::create([
            'vendor_id' => $vendor->id, 'make_id' => $make->id, 'model_id' => $model->id,
            'year' => 2021, 'body_type' => 'pickup', 'transmission' => 'manual',
            'fuel_type' => 'diesel', 'mileage' => 10000, 'color' => 'white',
            'condition' => 'used', 'price_usd' => 25000, 'status' => 'active',
        ]);
    }

    public function test_one_query_returns_both_vehicles_and_parts(): void
    {
        $this->get(route('search.index', ['q' => 'Toyota']))
            ->assertOk()
            ->assertSee('Vehicles')
            ->assertSee('Parts &amp; accessories', false)
            ->assertSee('Toyota Brake Pad Set')   // the part
            ->assertSee('2021 Toyota Hilux');      // the vehicle displayTitle
    }

    public function test_parts_only_query_shows_parts_section(): void
    {
        $this->get(route('search.index', ['q' => 'Brake Pad']))
            ->assertOk()
            ->assertSee('Toyota Brake Pad Set');
    }

    public function test_no_match_shows_rfq_request_cta(): void
    {
        $this->get(route('search.index', ['q' => 'zzzznomatchqxy']))
            ->assertOk()
            ->assertSee('Request it')
            ->assertSee('verified sellers will send you quotes');
    }

    public function test_empty_query_prompts(): void
    {
        $this->get(route('search.index'))
            ->assertOk()
            ->assertSee('search across vehicles and parts');
    }
}
