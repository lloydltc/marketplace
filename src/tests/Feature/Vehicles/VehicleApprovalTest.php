<?php

namespace Tests\Feature\Vehicles;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VehicleApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $user->assignRole('admin');
        return $user;
    }

    private function makeVendor(): Vendor
    {
        return Vendor::create([
            'name'          => 'Test Dealer',
            'slug'          => 'test-dealer-' . Str::random(4),
            'contact_email' => 'dealer@test.com',
            'status'        => 'approved',
        ]);
    }

    private function makeMakeAndModel(): array
    {
        $make  = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $model = VehicleModel::create(['make_id' => $make->id, 'name' => 'Corolla', 'slug' => 'corolla-' . Str::random(4)]);
        return [$make, $model];
    }

    private function makeVehicle(Vendor $vendor, string $status = 'pending'): Vehicle
    {
        [$make, $model] = $this->makeMakeAndModel();

        return Vehicle::create([
            'vendor_id'    => $vendor->id,
            'make_id'      => $make->id,
            'model_id'     => $model->id,
            'year'         => 2020,
            'body_type'    => 'sedan',
            'transmission' => 'manual',
            'fuel_type'    => 'petrol',
            'mileage'      => 50000,
            'color'        => 'silver',
            'condition'    => 'used',
            'status'       => $status,
            'price_zwl'    => 8000000.00,
        ]);
    }

    public function test_admin_can_approve_pending_vehicle(): void
    {
        $admin   = $this->makeAdmin();
        $vendor  = $this->makeVendor();
        $vehicle = $this->makeVehicle($vendor);

        $response = $this->actingAs($admin)
            ->post(route('admin.vehicles.approve', $vehicle));

        $response->assertRedirect(route('admin.vehicles.show', $vehicle));
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'status' => 'active']);
    }

    public function test_admin_can_reject_pending_vehicle_with_reason(): void
    {
        $admin   = $this->makeAdmin();
        $vendor  = $this->makeVendor();
        $vehicle = $this->makeVehicle($vendor);

        $response = $this->actingAs($admin)
            ->post(route('admin.vehicles.reject', $vehicle), ['reason' => 'Incomplete documentation.']);

        $response->assertRedirect(route('admin.vehicles.show', $vehicle));
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'status' => 'rejected']);
    }

    public function test_rejection_requires_reason(): void
    {
        $admin   = $this->makeAdmin();
        $vendor  = $this->makeVendor();
        $vehicle = $this->makeVehicle($vendor);

        $response = $this->actingAs($admin)
            ->post(route('admin.vehicles.reject', $vehicle), ['reason' => '']);

        $response->assertSessionHasErrors('reason');
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'status' => 'pending']);
    }

    public function test_non_admin_cannot_approve_vehicle(): void
    {
        $vendor  = $this->makeVendor();
        $vehicle = $this->makeVehicle($vendor);

        $vendorAdmin = User::factory()->create(['role' => 'vendor_admin', 'email_verified_at' => now()]);
        $vendorAdmin->assignRole('vendor_admin');

        $this->actingAs($vendorAdmin)
            ->post(route('admin.vehicles.approve', $vehicle))
            ->assertForbidden();

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'status' => 'pending']);
    }
}
