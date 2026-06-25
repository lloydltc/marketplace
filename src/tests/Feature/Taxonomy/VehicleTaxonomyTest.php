<?php

namespace Tests\Feature\Taxonomy;

use App\Modules\Vehicles\Models\VehicleGeneration;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use App\Modules\Vehicles\Models\VehicleVariant;
use App\Modules\Vehicles\Services\TaxonomyService;
use Database\Seeders\VehicleMakeSeeder;
use Database\Seeders\VehicleTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PM0: canonical taxonomy structure + cached cascade. Additive — must not disturb
 * the existing make/model rows that vehicle listings rely on.
 */
class VehicleTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_populate_the_full_cascade(): void
    {
        $this->seed(VehicleMakeSeeder::class);
        $this->seed(VehicleTaxonomySeeder::class);

        $this->assertDatabaseHas('vehicle_transmissions', ['type' => 'automatic']);
        $this->assertDatabaseHas('vehicle_engines', ['code' => '2.8 GD-6']);

        // Hilux gets its Revo generation.
        $hilux = VehicleModel::where('name', 'Hilux')->first();
        $this->assertNotNull($hilux);
        $this->assertTrue($hilux->generations()->where('name', 'Revo')->exists());
    }

    public function test_generation_and_variant_relations(): void
    {
        $make  = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $model = VehicleModel::create(['make_id' => $make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
        $gen   = VehicleGeneration::create(['model_id' => $model->id, 'name' => 'Revo', 'year_start' => 2015, 'year_end' => 2024]);
        $variant = VehicleVariant::create(['model_id' => $model->id, 'generation_id' => $gen->id, 'name' => '2.8 GD-6']);

        $this->assertTrue($model->generations->contains($gen));
        $this->assertSame($model->id, $variant->vehicleModel->id);
        $this->assertSame($gen->id, $variant->generation->id);
        $this->assertTrue($gen->variants->contains($variant));
    }

    public function test_cascade_service_returns_scoped_options(): void
    {
        $make  = VehicleMake::create(['name' => 'Nissan', 'slug' => 'nissan-' . Str::random(4), 'sort_order' => 0]);
        $model = VehicleModel::create(['make_id' => $make->id, 'name' => 'Navara', 'slug' => 'navara-' . Str::random(4)]);
        VehicleGeneration::create(['model_id' => $model->id, 'name' => 'NP300', 'year_start' => 2015, 'year_end' => 2024]);
        $other = VehicleMake::create(['name' => 'Mazda', 'slug' => 'mazda-' . Str::random(4), 'sort_order' => 1]);

        $svc = app(TaxonomyService::class);

        $this->assertTrue($svc->makes()->pluck('name')->contains('Nissan'));
        $this->assertSame(['Navara'], $svc->models($make->id)->pluck('name')->all());
        $this->assertSame([], $svc->models($other->id)->all());            // no models for Mazda
        $this->assertSame(['NP300'], $svc->generations($model->id)->pluck('name')->all());
    }

    public function test_inactive_options_are_excluded_and_flush_refreshes(): void
    {
        $make = VehicleMake::create(['name' => 'Honda', 'slug' => 'honda-' . Str::random(4), 'sort_order' => 0]);
        $svc  = app(TaxonomyService::class);

        $this->assertTrue($svc->makes()->pluck('name')->contains('Honda')); // warms cache

        $make->update(['is_active' => false]);
        $svc->flush();

        $this->assertFalse($svc->makes()->pluck('name')->contains('Honda'));
    }

    public function test_existing_make_model_rows_are_untouched(): void
    {
        // The additive columns default sensibly so legacy rows stay usable.
        $make = VehicleMake::create(['name' => 'Ford', 'slug' => 'ford-' . Str::random(4), 'sort_order' => 0]);
        $this->assertTrue($make->fresh()->is_active);
    }
}
