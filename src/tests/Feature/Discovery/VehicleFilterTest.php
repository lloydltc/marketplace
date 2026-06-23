<?php

namespace Tests\Feature\Discovery;

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
 * D3: buyers can filter vehicles by dynamic feature facets (D4), composed with
 * the standard filters; facet controls render; state is URL-encoded (GET).
 */
class VehicleFilterTest extends TestCase
{
    use RefreshDatabase;

    private VehicleMake $make;
    private VehicleModel $model;
    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->vendor = Vendor::create(['name' => 'D', 'slug' => 'd-' . Str::random(5), 'contact_email' => 'd@x.com', 'status' => 'approved']);
        $this->make = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
    }

    private function vehicle(int $year): Vehicle
    {
        return Vehicle::create([
            'vendor_id' => $this->vendor->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => $year, 'body_type' => 'pickup', 'transmission' => 'manual',
            'fuel_type' => 'diesel', 'mileage' => 1000, 'color' => 'white',
            'condition' => 'used', 'price_usd' => 20000, 'status' => 'active',
        ]);
    }

    public function test_filterable_facet_renders_on_catalogue(): void
    {
        FeatureDefinition::create(['name' => 'Parking Sensors', 'type' => 'boolean', 'is_filterable' => true, 'group' => 'Safety']);

        $this->get(route('vehicles.index'))
            ->assertOk()
            ->assertSee('Parking Sensors: any');
    }

    public function test_boolean_facet_narrows_results(): void
    {
        $sensors = FeatureDefinition::create(['name' => 'Parking Sensors', 'type' => 'boolean', 'is_filterable' => true]);
        $withSensors = $this->vehicle(2021);
        $withSensors->featureValues()->create(['feature_definition_id' => $sensors->id, 'value' => '1']);
        $this->vehicle(2019); // no sensors

        $this->get(route('vehicles.index', ['features' => [$sensors->id => '1']]))
            ->assertOk()
            ->assertSee('2021 Toyota Hilux')
            ->assertDontSee('2019 Toyota Hilux');
    }

    public function test_enum_facet_narrows_results(): void
    {
        $drive = FeatureDefinition::create(['name' => 'Drivetrain', 'type' => 'enum', 'options' => ['FWD', 'AWD'], 'is_filterable' => true]);
        $awd = $this->vehicle(2022);
        $awd->featureValues()->create(['feature_definition_id' => $drive->id, 'value' => 'AWD']);
        $fwd = $this->vehicle(2018);
        $fwd->featureValues()->create(['feature_definition_id' => $drive->id, 'value' => 'FWD']);

        $this->get(route('vehicles.index', ['features' => [$drive->id => 'AWD']]))
            ->assertOk()
            ->assertSee('2022 Toyota Hilux')
            ->assertDontSee('2018 Toyota Hilux');
    }

    public function test_facet_composes_with_standard_filter(): void
    {
        $sensors = FeatureDefinition::create(['name' => 'Parking Sensors', 'type' => 'boolean', 'is_filterable' => true]);
        $a = $this->vehicle(2021);
        $a->featureValues()->create(['feature_definition_id' => $sensors->id, 'value' => '1']);
        $b = $this->vehicle(2010);
        $b->featureValues()->create(['feature_definition_id' => $sensors->id, 'value' => '1']);

        // Feature AND year_min compose: both have sensors, only 2021 passes year_min.
        $this->get(route('vehicles.index', ['features' => [$sensors->id => '1'], 'year_min' => 2015]))
            ->assertOk()
            ->assertSee('2021 Toyota Hilux')
            ->assertDontSee('2010 Toyota Hilux');
    }
}
