<?php

namespace Tests\Feature\Parts;

use App\Modules\Parts\Models\Part;
use App\Modules\Vehicles\Models\VehicleGeneration;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use App\Modules\Vehicles\Models\VehicleVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PM3: canonical fitment matching — null-dimension (applies-to-all), year-range
 * edges, universal parts, mismatch, cascade endpoints, and the session context.
 */
class FitmentEngineTest extends TestCase
{
    use RefreshDatabase;

    private VehicleMake $make;
    private VehicleModel $model;
    private VehicleGeneration $gen;
    private VehicleVariant $variantA;
    private VehicleVariant $variantB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->make  = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
        $this->gen   = VehicleGeneration::create(['model_id' => $this->model->id, 'name' => 'Revo', 'year_start' => 2015, 'year_end' => 2024]);
        $this->variantA = VehicleVariant::create(['model_id' => $this->model->id, 'generation_id' => $this->gen->id, 'name' => '2.8 GD-6']);
        $this->variantB = VehicleVariant::create(['model_id' => $this->model->id, 'generation_id' => $this->gen->id, 'name' => '2.4 GD-6']);
    }

    private function selection(array $overrides = []): array
    {
        return array_merge([
            'make_id' => $this->make->id, 'model_id' => $this->model->id, 'year' => 2018,
            'generation_id' => null, 'variant_id' => null, 'engine_id' => null, 'transmission_id' => null,
        ], $overrides);
    }

    public function test_year_range_match_and_edges(): void
    {
        $part = Part::create(['name' => 'Revo Brake Pads']);
        $part->fitments()->create(['make_id' => $this->make->id, 'model_id' => $this->model->id, 'year_start' => 2015, 'year_end' => 2024]);

        $this->assertTrue($part->fitsSelection($this->selection(['year' => 2015])));  // lower edge
        $this->assertTrue($part->fitsSelection($this->selection(['year' => 2024])));  // upper edge
        $this->assertFalse($part->fitsSelection($this->selection(['year' => 2014]))); // below
        $this->assertFalse($part->fitsSelection($this->selection(['year' => 2025]))); // above
    }

    public function test_null_dimension_applies_to_all(): void
    {
        // Fitment leaves variant null → fits any variant selection.
        $part = Part::create(['name' => 'Generic Hilux Filter']);
        $part->fitments()->create(['make_id' => $this->make->id, 'model_id' => $this->model->id, 'year_start' => 2015, 'year_end' => 2024]);

        $this->assertTrue($part->fitsSelection($this->selection(['variant_id' => $this->variantA->id])));
        $this->assertTrue($part->fitsSelection($this->selection(['variant_id' => $this->variantB->id])));
    }

    public function test_specific_dimension_must_match(): void
    {
        // Fitment pinned to variant A → only fits variant A.
        $part = Part::create(['name' => 'GD-6 Specific Part']);
        $part->fitments()->create([
            'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'variant_id' => $this->variantA->id, 'year_start' => 2015, 'year_end' => 2024,
        ]);

        $this->assertTrue($part->fitsSelection($this->selection(['variant_id' => $this->variantA->id])));
        $this->assertFalse($part->fitsSelection($this->selection(['variant_id' => $this->variantB->id])));
    }

    public function test_universal_part_fits_anything(): void
    {
        $part = Part::create(['name' => 'Universal Floor Mat', 'is_universal' => true]);

        $this->assertTrue($part->fitsSelection($this->selection()));
        $other = VehicleMake::create(['name' => 'Mazda', 'slug' => 'mazda-' . Str::random(4), 'sort_order' => 1]);
        $this->assertTrue($part->fitsSelection($this->selection(['make_id' => $other->id])));
    }

    public function test_compatible_with_scope_filters_catalog(): void
    {
        $fits = Part::create(['name' => 'Fits']);
        $fits->fitments()->create(['make_id' => $this->make->id, 'model_id' => $this->model->id, 'year_start' => 2015, 'year_end' => 2024]);
        $universal = Part::create(['name' => 'Universal', 'is_universal' => true]);
        $nope = Part::create(['name' => 'Nope']); // no fitment, not universal

        $ids = Part::active()->compatibleWith($this->selection())->pluck('id');

        $this->assertTrue($ids->contains($fits->id));
        $this->assertTrue($ids->contains($universal->id));
        $this->assertFalse($ids->contains($nope->id));
    }

    public function test_cascade_endpoints_return_scoped_options(): void
    {
        $this->getJson(route('fitment.models', ['make_id' => $this->make->id]))
            ->assertOk()->assertJsonFragment(['name' => 'Hilux']);

        $this->getJson(route('fitment.generations', ['model_id' => $this->model->id]))
            ->assertOk()->assertJsonFragment(['name' => 'Revo']);

        $this->getJson(route('fitment.variants', ['model_id' => $this->model->id]))
            ->assertOk()->assertJsonFragment(['name' => '2.8 GD-6']);
    }

    public function test_selecting_a_vehicle_stores_fitment_context(): void
    {
        $this->post(route('fitment.select'), [
            'make_id' => $this->make->id, 'model_id' => $this->model->id, 'year' => 2018,
        ])
            ->assertRedirect()
            ->assertSessionHas('fitment.selection', fn ($sel) => $sel['model_id'] === $this->model->id
                && str_contains($sel['label'], 'Hilux'));

        $this->post(route('fitment.clear'))
            ->assertRedirect()
            ->assertSessionMissing('fitment.selection');
    }
}
