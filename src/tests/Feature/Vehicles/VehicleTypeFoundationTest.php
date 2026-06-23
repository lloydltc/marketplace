<?php

namespace Tests\Feature\Vehicles;

use App\Models\User;
use App\Models\Vendor;
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
 * H0: vehicle-type foundation — the chooser, type-scoped body-types + features,
 * type-aware persistence, and catalogue type filtering across all four types.
 */
class VehicleTypeFoundationTest extends TestCase
{
    use RefreshDatabase;

    private VehicleMake $make;
    private VehicleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->make = VehicleMake::create(['name' => 'Yamaha', 'slug' => 'yamaha-' . Str::random(4), 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'MT-07', 'slug' => 'mt07-' . Str::random(4)]);
    }

    private function seller(): User
    {
        $u = User::factory()->create(['role' => 'private_seller', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('private_seller');

        return $u;
    }

    public function test_create_shows_type_chooser_when_no_type(): void
    {
        $this->actingAs($this->seller())->get(route('seller.vehicles.create'))
            ->assertOk()
            ->assertSee('What are you listing?')
            ->assertSee('Bikes')
            ->assertSee('Boats')
            ->assertSee('Trailers');
    }

    public function test_motorbike_form_uses_type_scoped_body_types(): void
    {
        $this->actingAs($this->seller())->get(route('seller.vehicles.create', ['type' => 'motorbike']))
            ->assertOk()
            ->assertSee('name="vehicle_type" value="motorbike"', false)
            ->assertSee('Cruiser')   // motorbike body type
            ->assertDontSee('>Sedan<', false); // car-only body type
    }

    public function test_seller_creates_typed_listing(): void
    {
        $this->actingAs($this->seller())->post(route('seller.vehicles.store'), [
            'vehicle_type' => 'motorbike',
            'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2022, 'body_type' => 'cruiser', 'transmission' => 'manual',
            'fuel_type' => 'petrol', 'mileage' => 5000, 'color' => 'black', 'condition' => 'used',
            'price_usd' => 6000,
        ])->assertRedirect();

        $this->assertDatabaseHas('vehicles', ['vehicle_type' => 'motorbike', 'body_type' => 'cruiser']);
    }

    public function test_listing_without_type_defaults_to_vehicle(): void
    {
        $v = Vehicle::create([
            'user_id' => $this->seller()->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2020, 'body_type' => 'sedan', 'transmission' => 'manual', 'fuel_type' => 'petrol',
            'mileage' => 1, 'color' => 'red', 'condition' => 'used', 'price_usd' => 1000, 'status' => 'active',
        ]);

        $this->assertSame('vehicle', $v->fresh()->vehicle_type);
    }

    public function test_feature_is_type_scoped_in_the_form(): void
    {
        // A car-only feature must not appear on a motorbike form.
        FeatureDefinition::create(['name' => 'Number of Doors', 'type' => 'number', 'unit' => 'doors', 'applies_to_types' => ['vehicle']]);
        FeatureDefinition::create(['name' => 'Has Topbox', 'type' => 'boolean', 'applies_to_types' => ['motorbike']]);

        $res = $this->actingAs($this->seller())->get(route('seller.vehicles.create', ['type' => 'motorbike']))->assertOk();
        $res->assertSee('Has Topbox');
        $res->assertDontSee('Number of Doors');
    }

    public function test_catalogue_filters_by_type(): void
    {
        $vendor = Vendor::create(['name' => 'D', 'slug' => 'd-' . Str::random(5), 'contact_email' => 'd@x.com', 'status' => 'approved']);
        $car  = $this->typedVehicle($vendor, 'vehicle', 2021);
        $boat = $this->typedVehicle($vendor, 'boat', 2019);

        $this->get(route('vehicles.index', ['vehicle_type' => 'boat']))
            ->assertOk()
            ->assertSee('2019 Yamaha MT-07')
            ->assertDontSee('2021 Yamaha MT-07');
    }

    private function typedVehicle(Vendor $vendor, string $type, int $year): Vehicle
    {
        return Vehicle::create([
            'vendor_id' => $vendor->id, 'vehicle_type' => $type, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => $year, 'body_type' => 'other', 'transmission' => 'manual', 'fuel_type' => 'petrol',
            'mileage' => 1, 'color' => 'blue', 'condition' => 'used', 'price_usd' => 5000, 'status' => 'active',
            'expires_at' => now()->addDays(10),
        ]);
    }
}
