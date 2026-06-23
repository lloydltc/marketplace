<?php

namespace Tests\Feature\Vehicles;

use App\Models\User;
use App\Modules\Vehicles\Models\FeatureDefinition;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * D4: admin-managed dynamic vehicle features — admin defines them, sellers set
 * them on a listing without code changes, buyers see them on the detail page.
 */
class DynamicFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private VehicleMake $make;
    private VehicleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->make  = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
    }

    private function admin(): User
    {
        $u = User::factory()->create(['role' => 'super_admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('super_admin');

        return $u;
    }

    private function seller(): User
    {
        $u = User::factory()->create(['role' => 'private_seller', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('private_seller');

        return $u;
    }

    public function test_admin_can_define_a_feature_without_code_change(): void
    {
        $this->actingAs($this->admin())->post(route('admin.vehicle-features.store'), [
            'name' => 'Parking Sensors', 'type' => 'boolean', 'group' => 'Safety',
            'is_filterable' => '1', 'sort_order' => 1,
        ])->assertRedirect(route('admin.vehicle-features.index'));

        $this->assertDatabaseHas('feature_definitions', [
            'key' => 'parking_sensors', 'type' => 'boolean', 'is_filterable' => true,
        ]);
    }

    public function test_non_admin_cannot_manage_features(): void
    {
        $this->actingAs($this->seller())->get(route('admin.vehicle-features.index'))->assertForbidden();
    }

    public function test_enum_feature_requires_options(): void
    {
        $this->actingAs($this->admin())->post(route('admin.vehicle-features.store'), [
            'name' => 'Drivetrain', 'type' => 'enum', 'options' => '',
        ])->assertSessionHasErrors('options');
    }

    public function test_seller_sets_features_on_create_and_buyer_sees_them(): void
    {
        $doors   = FeatureDefinition::create(['name' => 'Doors', 'type' => 'number', 'unit' => 'doors', 'is_filterable' => true, 'group' => 'Key specs']);
        $sensors = FeatureDefinition::create(['name' => 'Parking Sensors', 'type' => 'boolean', 'is_filterable' => true, 'group' => 'Safety']);

        $seller = $this->seller();
        $this->actingAs($seller)->post(route('seller.vehicles.store'), [
            'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2021, 'body_type' => 'pickup', 'transmission' => 'manual',
            'fuel_type' => 'diesel', 'mileage' => 20000, 'color' => 'white', 'condition' => 'used',
            'price_usd' => 15000,
            'features' => [$doors->id => '5', $sensors->id => '1'],
        ])->assertRedirect();

        $vehicle = Vehicle::where('user_id', $seller->id)->firstOrFail();
        $this->assertDatabaseHas('vehicle_feature_values', [
            'vehicle_id' => $vehicle->id, 'feature_definition_id' => $doors->id, 'value' => '5',
        ]);
        $this->assertDatabaseHas('vehicle_feature_values', [
            'vehicle_id' => $vehicle->id, 'feature_definition_id' => $sensors->id, 'value' => '1',
        ]);

        // Approve so it's public, then the buyer detail page shows the specs.
        $vehicle->update(['status' => 'active']);
        $this->get(route('vehicles.show', $vehicle))
            ->assertOk()
            ->assertSee('Features &amp; specs', false)
            ->assertSee('Doors')
            ->assertSee('5 doors')
            ->assertSee('Parking Sensors');
    }

    public function test_blank_feature_value_clears_it_on_update(): void
    {
        $doors = FeatureDefinition::create(['name' => 'Doors', 'type' => 'number', 'unit' => 'doors']);
        $seller = $this->seller();
        $vehicle = Vehicle::create([
            'user_id' => $seller->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2021, 'body_type' => 'pickup', 'transmission' => 'manual', 'fuel_type' => 'diesel',
            'mileage' => 1000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 15000, 'status' => 'pending',
        ]);
        $vehicle->featureValues()->create(['feature_definition_id' => $doors->id, 'value' => '5']);

        $this->actingAs($seller)->put(route('seller.vehicles.update', $vehicle), [
            'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2021, 'body_type' => 'pickup', 'transmission' => 'manual',
            'fuel_type' => 'diesel', 'mileage' => 1000, 'color' => 'white', 'condition' => 'used',
            'price_usd' => 15000,
            'features' => [$doors->id => ''], // cleared
        ])->assertRedirect();

        $this->assertDatabaseMissing('vehicle_feature_values', [
            'vehicle_id' => $vehicle->id, 'feature_definition_id' => $doors->id,
        ]);
    }
}
