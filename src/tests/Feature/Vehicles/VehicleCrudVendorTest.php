<?php

namespace Tests\Feature\Vehicles;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleCrudVendorTest extends TestCase
{
    use RefreshDatabase;

    private User $vendorAdmin;
    private Vendor $vendor;
    private VehicleMake $make;
    private VehicleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->vendor = Vendor::create([
            'name'          => 'Test Dealer',
            'slug'          => 'test-dealer',
            'contact_email' => 'dealer@test.com',
            'status'        => 'approved',
        ]);

        $this->vendorAdmin = User::factory()->create(['role' => 'vendor_admin', 'email_verified_at' => now()]);
        $this->vendorAdmin->assignRole('vendor_admin');
        $this->vendorAdmin->vendors()->attach($this->vendor->id, [
            'vendor_role' => 'admin',
            'invited_at'  => now(),
            'joined_at'   => now(),
        ]);

        $this->make  = VehicleMake::create(['name' => 'Nissan', 'slug' => 'nissan', 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Navara', 'slug' => 'navara']);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'make_id'      => $this->make->id,
            'model_id'     => $this->model->id,
            'year'         => 2021,
            'body_type'    => 'pickup',
            'transmission' => 'manual',
            'fuel_type'    => 'diesel',
            'mileage'      => 80000,
            'color'        => 'white',
            'condition'    => 'used',
            'price_zwl'    => 12000000.00,
        ], $overrides);
    }

    public function test_vendor_admin_can_create_vehicle(): void
    {
        $response = $this->actingAs($this->vendorAdmin)
            ->post(route('vendor.vehicles.store'), $this->validPayload());

        $response->assertRedirect();
        $this->assertDatabaseHas('vehicles', [
            'vendor_id' => $this->vendor->id,
            'make_id'   => $this->make->id,
            'status'    => 'pending',
        ]);
    }

    public function test_vehicle_requires_price_zwl(): void
    {
        $payload = $this->validPayload();
        unset($payload['price_zwl']);

        $this->actingAs($this->vendorAdmin)
            ->post(route('vendor.vehicles.store'), $payload)
            ->assertSessionHasErrors('price_zwl');
    }

    public function test_vendor_admin_can_edit_pending_vehicle(): void
    {
        $vehicle = Vehicle::create(array_merge($this->validPayload(), [
            'vendor_id' => $this->vendor->id,
            'status'    => 'pending',
        ]));

        $response = $this->actingAs($this->vendorAdmin)
            ->put(route('vendor.vehicles.update', $vehicle), $this->validPayload(['price_zwl' => 13000000.00]));

        $response->assertRedirect();
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'price_zwl' => 13000000.00]);
    }

    public function test_vendor_admin_cannot_edit_active_vehicle(): void
    {
        $vehicle = Vehicle::create(array_merge($this->validPayload(), [
            'vendor_id' => $this->vendor->id,
            'status'    => 'active',
        ]));

        $this->actingAs($this->vendorAdmin)
            ->put(route('vendor.vehicles.update', $vehicle), $this->validPayload(['price_zwl' => 99.00]))
            ->assertForbidden();
    }

    public function test_vendor_cannot_access_another_vendors_vehicle(): void
    {
        $otherVendor = Vendor::create([
            'name'          => 'Other Dealer',
            'slug'          => 'other-dealer',
            'contact_email' => 'other@test.com',
            'status'        => 'approved',
        ]);

        $otherVehicle = Vehicle::create(array_merge($this->validPayload(), [
            'vendor_id' => $otherVendor->id,
            'status'    => 'pending',
        ]));

        $this->actingAs($this->vendorAdmin)
            ->get(route('vendor.vehicles.edit', $otherVehicle))
            ->assertForbidden();
    }

    public function test_vendor_admin_can_delete_pending_vehicle(): void
    {
        $vehicle = Vehicle::create(array_merge($this->validPayload(), [
            'vendor_id' => $this->vendor->id,
            'status'    => 'pending',
        ]));

        $this->actingAs($this->vendorAdmin)
            ->delete(route('vendor.vehicles.destroy', $vehicle))
            ->assertRedirect(route('vendor.vehicles.index'));

        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
    }
}
