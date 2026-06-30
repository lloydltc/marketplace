<?php

namespace Tests\Feature\Engagement;

use App\Models\User;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * AC4: recently-viewed + sponsored re-engagement surfaces on the landing.
 * These reuse the H7/H8 implementations — this gate confirms they co-exist and
 * the sponsored row is driven by the promotion model (featured_until).
 */
class ReEngagementSurfacesTest extends TestCase
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

    private function vehicle(array $attrs = []): Vehicle
    {
        $seller = User::factory()->create(['role' => 'private_seller', 'status' => 'active']);

        return Vehicle::create(array_merge([
            'user_id' => $seller->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2019, 'body_type' => 'pickup', 'transmission' => 'manual', 'fuel_type' => 'diesel',
            'mileage' => 1000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 15000,
            'vehicle_type' => 'vehicle', 'status' => 'active', 'expires_at' => now()->addDays(10),
        ], $attrs));
    }

    public function test_sponsored_row_reflects_promotion_model(): void
    {
        $this->vehicle(['year' => 2017, 'featured_until' => now()->addDays(5)]);

        $this->get(route('home'))->assertOk()->assertSee('Sponsored listings')->assertSee('2017 Toyota Hilux');
    }

    public function test_recently_viewed_surfaces_on_landing(): void
    {
        $v = $this->vehicle(['year' => 2016]);

        $this->withUnencryptedCookie('recently_viewed_vehicles', $v->id)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Recently viewed')
            ->assertSee('2016 Toyota Hilux');
    }
}
