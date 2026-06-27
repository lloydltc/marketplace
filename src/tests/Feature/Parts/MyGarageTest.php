<?php

namespace Tests\Feature\Parts;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Parts\Models\GarageVehicle;
use App\Modules\Parts\Models\Part;
use App\Modules\Products\Models\Product;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PM7: My Garage — save vehicles, activate to drive fitment context, ownership.
 */
class MyGarageTest extends TestCase
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

    private function customer(): User
    {
        $u = User::factory()->create(['role' => 'customer', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('customer');

        return $u;
    }

    public function test_customer_saves_a_vehicle_and_first_is_default(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->post(route('garage.store'), [
            'make_id' => $this->make->id, 'model_id' => $this->model->id, 'year' => 2018, 'nickname' => 'My Bakkie',
        ])->assertRedirect(route('garage.index'));

        $vehicle = GarageVehicle::where('user_id', $customer->id)->first();
        $this->assertNotNull($vehicle);
        $this->assertTrue($vehicle->is_default);
        $this->assertSame('My Bakkie', $vehicle->label());
    }

    public function test_saving_activates_fitment_context(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->post(route('garage.store'), [
            'make_id' => $this->make->id, 'model_id' => $this->model->id, 'year' => 2018,
        ])->assertSessionHas('fitment.selection', fn ($sel) => $sel['model_id'] === $this->model->id);
    }

    public function test_activating_a_saved_vehicle_filters_the_catalog(): void
    {
        $customer = $this->customer();
        $vehicle = GarageVehicle::create([
            'user_id' => $customer->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id, 'year' => 2018,
        ]);

        $this->actingAs($customer)->post(route('garage.activate', $vehicle))
            ->assertRedirect(route('parts.index'))
            ->assertSessionHas('fitment.selection', fn ($sel) => $sel['make_id'] === $this->make->id);
    }

    public function test_garage_index_lists_saved_vehicles(): void
    {
        $customer = $this->customer();
        GarageVehicle::create(['user_id' => $customer->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id, 'year' => 2020, 'nickname' => 'Daily']);

        $this->actingAs($customer)->get(route('garage.index'))
            ->assertOk()->assertSee('Daily');
    }

    public function test_cannot_activate_or_delete_another_users_vehicle(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        $vehicle = GarageVehicle::create(['user_id' => $owner->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id]);

        $this->actingAs($other)->post(route('garage.activate', $vehicle))->assertForbidden();
        $this->actingAs($other)->delete(route('garage.destroy', $vehicle))->assertForbidden();
    }

    public function test_non_customer_cannot_reach_garage(): void
    {
        $seller = User::factory()->create(['role' => 'private_seller', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $seller->assignRole('private_seller');

        $this->actingAs($seller)->get(route('garage.index'))->assertForbidden();
    }
}
