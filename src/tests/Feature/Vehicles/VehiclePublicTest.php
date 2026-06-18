<?php

namespace Tests\Feature\Vehicles;

use App\Models\User;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehiclePublicTest extends TestCase
{
    use RefreshDatabase;

    private VehicleMake $make;
    private VehicleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->make  = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota', 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Hilux', 'slug' => 'hilux']);
    }

    private function makeVehicle(string $status, array $overrides = []): Vehicle
    {
        $seller = User::factory()->create(['role' => 'private_seller', 'email_verified_at' => now()]);
        $seller->assignRole('private_seller');

        return Vehicle::create(array_merge([
            'user_id'      => $seller->id,
            'make_id'      => $this->make->id,
            'model_id'     => $this->model->id,
            'year'         => 2022,
            'body_type'    => 'pickup',
            'transmission' => 'manual',
            'fuel_type'    => 'diesel',
            'mileage'      => 30000,
            'color'        => 'black',
            'condition'    => 'used',
            'status'       => $status,
            'price_zwl'    => 15000000.00,
        ], $overrides));
    }

    public function test_public_index_shows_only_active_vehicles(): void
    {
        $active  = $this->makeVehicle('active', ['year' => 2022]);
        $pending = $this->makeVehicle('pending', ['year' => 2018]);

        $response = $this->get(route('vehicles.index'));

        $response->assertOk();
        $response->assertSee($active->displayTitle());
        $response->assertDontSee($pending->displayTitle());
    }

    public function test_public_can_view_active_vehicle(): void
    {
        $vehicle = $this->makeVehicle('active');

        $this->get(route('vehicles.show', $vehicle))->assertOk();
    }

    public function test_pending_vehicle_returns_404_on_public_show(): void
    {
        $vehicle = $this->makeVehicle('pending');

        $this->get(route('vehicles.show', $vehicle))->assertNotFound();
    }

    public function test_rejected_vehicle_returns_404_on_public_show(): void
    {
        $vehicle = $this->makeVehicle('rejected');

        $this->get(route('vehicles.show', $vehicle))->assertNotFound();
    }

    public function test_public_index_can_filter_by_make(): void
    {
        $otherMake  = VehicleMake::create(['name' => 'Honda', 'slug' => 'honda', 'sort_order' => 1]);
        $otherModel = VehicleModel::create(['make_id' => $otherMake->id, 'name' => 'CR-V', 'slug' => 'cr-v']);

        $this->makeVehicle('active');
        $this->makeVehicle('active', ['make_id' => $otherMake->id, 'model_id' => $otherModel->id]);

        $response = $this->get(route('vehicles.index', ['make_id' => $this->make->id]));

        $response->assertOk();
        $response->assertSee('Hilux');
        $response->assertDontSee('CR-V');
    }
}
