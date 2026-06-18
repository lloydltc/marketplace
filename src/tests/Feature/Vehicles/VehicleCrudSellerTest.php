<?php

namespace Tests\Feature\Vehicles;

use App\Models\User;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleCrudSellerTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;
    private VehicleMake $make;
    private VehicleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->seller = User::factory()->create(['role' => 'private_seller', 'email_verified_at' => now()]);
        $this->seller->assignRole('private_seller');

        $this->make  = VehicleMake::create(['name' => 'Mazda', 'slug' => 'mazda', 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'CX-5', 'slug' => 'cx-5']);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'make_id'      => $this->make->id,
            'model_id'     => $this->model->id,
            'year'         => 2019,
            'body_type'    => 'suv',
            'transmission' => 'automatic',
            'fuel_type'    => 'petrol',
            'mileage'      => 70000,
            'color'        => 'blue',
            'condition'    => 'used',
            'price_zwl'    => 9500000.00,
        ], $overrides);
    }

    public function test_private_seller_can_create_vehicle_listing(): void
    {
        $response = $this->actingAs($this->seller)
            ->post(route('seller.vehicles.store'), $this->validPayload());

        $response->assertRedirect();
        $this->assertDatabaseHas('vehicles', [
            'user_id'   => $this->seller->id,
            'vendor_id' => null,
            'status'    => 'pending',
        ]);
    }

    public function test_private_seller_can_edit_own_rejected_vehicle(): void
    {
        $vehicle = Vehicle::create(array_merge($this->validPayload(), [
            'user_id' => $this->seller->id,
            'status'  => 'rejected',
        ]));

        $response = $this->actingAs($this->seller)
            ->put(route('seller.vehicles.update', $vehicle), $this->validPayload(['price_zwl' => 10000000.00]));

        $response->assertRedirect();
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'price_zwl' => 10000000.00, 'status' => 'pending']);
    }

    public function test_private_seller_cannot_edit_another_sellers_vehicle(): void
    {
        $otherSeller = User::factory()->create(['role' => 'private_seller', 'email_verified_at' => now()]);
        $otherSeller->assignRole('private_seller');

        $otherVehicle = Vehicle::create(array_merge($this->validPayload(), [
            'user_id' => $otherSeller->id,
            'status'  => 'pending',
        ]));

        $this->actingAs($this->seller)
            ->put(route('seller.vehicles.update', $otherVehicle), $this->validPayload())
            ->assertForbidden();
    }

    public function test_private_seller_can_delete_own_vehicle(): void
    {
        $vehicle = Vehicle::create(array_merge($this->validPayload(), [
            'user_id' => $this->seller->id,
            'status'  => 'pending',
        ]));

        $this->actingAs($this->seller)
            ->delete(route('seller.vehicles.destroy', $vehicle))
            ->assertRedirect(route('seller.vehicles.index'));

        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
    }

    public function test_seller_cannot_delete_another_sellers_vehicle(): void
    {
        $otherSeller = User::factory()->create(['role' => 'private_seller', 'email_verified_at' => now()]);
        $otherSeller->assignRole('private_seller');

        $otherVehicle = Vehicle::create(array_merge($this->validPayload(), [
            'user_id' => $otherSeller->id,
            'status'  => 'pending',
        ]));

        $this->actingAs($this->seller)
            ->delete(route('seller.vehicles.destroy', $otherVehicle))
            ->assertForbidden();
    }
}
