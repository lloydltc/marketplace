<?php

namespace Tests\Feature\Discovery;

use App\Models\User;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use App\Modules\Vehicles\Repositories\VehicleRepositoryInterface;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * H6: discovery — live inventory counts, count-driven type tabs, and the
 * browse-by-body-type / browse-by-make rails. Counts must only reflect what a
 * buyer can actually open (active + unexpired).
 */
class VehicleDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    private VehicleMake $make;
    private VehicleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->make = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
    }

    private function seller(): User
    {
        $u = User::factory()->create(['role' => 'private_seller', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('private_seller');

        return $u;
    }

    private function vehicle(array $attrs = []): Vehicle
    {
        return Vehicle::create(array_merge([
            'user_id' => $this->seller()->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2021, 'body_type' => 'sedan', 'transmission' => 'manual', 'fuel_type' => 'petrol',
            'mileage' => 1000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 15000,
            'vehicle_type' => 'vehicle', 'status' => 'active', 'expires_at' => now()->addDays(10),
        ], $attrs));
    }

    public function test_count_public_excludes_unpublishable_listings(): void
    {
        $this->vehicle();                                   // active + unexpired  ✓
        $this->vehicle();                                   // active + unexpired  ✓
        $this->vehicle(['status' => 'draft']);              // draft               ✗
        $this->vehicle(['status' => 'pending']);            // awaiting review     ✗
        $this->vehicle(['expires_at' => now()->subDay()]);  // lapsed              ✗

        $this->assertSame(2, app(VehicleRepositoryInterface::class)->countPublic());
    }

    public function test_count_public_respects_filters(): void
    {
        $this->vehicle(['body_type' => 'sedan']);
        $this->vehicle(['body_type' => 'suv']);
        $this->vehicle(['body_type' => 'suv']);

        $repo = app(VehicleRepositoryInterface::class);
        $this->assertSame(2, $repo->countPublic(['body_type' => 'suv']));
        $this->assertSame(1, $repo->countPublic(['body_type' => 'sedan']));
    }

    public function test_count_by_type_groups_active_inventory(): void
    {
        $this->vehicle(['vehicle_type' => 'vehicle']);
        $this->vehicle(['vehicle_type' => 'vehicle']);
        $this->vehicle(['vehicle_type' => 'motorbike']);
        $this->vehicle(['vehicle_type' => 'motorbike', 'status' => 'draft']); // excluded

        $counts = app(VehicleRepositoryInterface::class)->countByType();
        $this->assertSame(2, $counts['vehicle']);
        $this->assertSame(1, $counts['motorbike']);
    }

    public function test_popular_makes_only_includes_makes_with_live_inventory(): void
    {
        // A make with zero live listings must not surface in the rail.
        VehicleMake::create(['name' => 'Mazda', 'slug' => 'mazda-' . Str::random(4), 'sort_order' => 1]);
        $this->vehicle();
        $this->vehicle();

        $makes = app(VehicleRepositoryInterface::class)->popularMakes();
        $this->assertCount(1, $makes);
        $this->assertSame('Toyota', $makes->first()->name);
        $this->assertSame(2, $makes->first()->total);
    }

    public function test_live_count_endpoint_returns_filtered_count(): void
    {
        $this->vehicle(['body_type' => 'suv']);
        $this->vehicle(['body_type' => 'suv']);
        $this->vehicle(['body_type' => 'sedan']);

        $this->getJson(route('search.vehicles.count', ['body_type' => 'suv']))
            ->assertOk()
            ->assertJson(['count' => 2, 'label' => 'Show 2 vehicles']);

        $this->getJson(route('search.vehicles.count', ['body_type' => 'sedan']))
            ->assertOk()
            ->assertJson(['count' => 1, 'label' => 'Show 1 vehicle']);
    }

    public function test_index_renders_type_tabs_and_body_rail_with_counts(): void
    {
        $this->vehicle(['body_type' => 'suv']);
        $this->vehicle(['body_type' => 'suv']);

        $this->get(route('vehicles.index'))
            ->assertOk()
            ->assertSee('count: 2', false)        // live-count seed in the Alpine x-data
            ->assertSee('2 vehicles found')       // results header
            ->assertSee('body_type=suv');         // body-type browse rail link
    }

    public function test_home_renders_browse_by_make(): void
    {
        $this->vehicle();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Browse by make')
            ->assertSeeText('Toyota');
    }
}
