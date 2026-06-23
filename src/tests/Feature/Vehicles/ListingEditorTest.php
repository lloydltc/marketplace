<?php

namespace Tests\Feature\Vehicles;

use App\Models\User;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * H1: listing editor with Draft / Publish / Delete. Drafts persist partial input
 * and stay private; publishing enforces full validation and enters review.
 */
class ListingEditorTest extends TestCase
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

    private function seller(): User
    {
        $u = User::factory()->create(['role' => 'private_seller', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('private_seller');

        return $u;
    }

    private function fullPayload(array $extra = []): array
    {
        return array_merge([
            'vehicle_type' => 'vehicle',
            'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2021, 'body_type' => 'sedan', 'transmission' => 'manual',
            'fuel_type' => 'petrol', 'mileage' => 1000, 'color' => 'white',
            'condition' => 'used', 'price_usd' => 15000,
        ], $extra);
    }

    public function test_save_as_draft_persists_partial_input_privately(): void
    {
        // Only a type + make/model — no price, no body type, etc.
        $this->actingAs($this->seller())->post(route('seller.vehicles.store'), [
            'action' => 'draft',
            'vehicle_type' => 'vehicle',
            'make_id' => $this->make->id, 'model_id' => $this->model->id,
        ])->assertRedirect();

        $vehicle = Vehicle::firstOrFail();
        $this->assertSame('draft', $vehicle->status);
        $this->assertNull($vehicle->price_usd);
        $this->assertNull($vehicle->body_type);
    }

    public function test_draft_is_not_publicly_visible(): void
    {
        $seller = $this->seller();
        Vehicle::create($this->fullPayload(['user_id' => $seller->id, 'status' => 'draft', 'year' => 2021]));

        $this->get(route('vehicles.index'))->assertOk()->assertDontSee('2021 Toyota Hilux');
    }

    public function test_publish_requires_full_data(): void
    {
        $this->actingAs($this->seller())->post(route('seller.vehicles.store'), [
            'action' => 'publish',
            'vehicle_type' => 'vehicle',
            'make_id' => $this->make->id, 'model_id' => $this->model->id,
            // missing year/body_type/etc.
        ])->assertSessionHasErrors(['year', 'body_type', 'condition']);

        $this->assertSame(0, Vehicle::count());
    }

    public function test_publish_creates_pending_listing(): void
    {
        $this->actingAs($this->seller())->post(route('seller.vehicles.store'), $this->fullPayload(['action' => 'publish']))
            ->assertRedirect();

        $this->assertSame('pending', Vehicle::firstOrFail()->status);
    }

    public function test_publishing_a_draft_moves_it_to_review(): void
    {
        $seller = $this->seller();
        $draft = Vehicle::create($this->fullPayload(['user_id' => $seller->id, 'status' => 'draft']));

        $this->actingAs($seller)->put(route('seller.vehicles.update', $draft), $this->fullPayload(['action' => 'publish']))
            ->assertRedirect();

        $this->assertSame('pending', $draft->fresh()->status);
    }

    public function test_saving_a_draft_keeps_it_draft(): void
    {
        $seller = $this->seller();
        $draft = Vehicle::create($this->fullPayload(['user_id' => $seller->id, 'status' => 'draft']));

        $this->actingAs($seller)->put(route('seller.vehicles.update', $draft), $this->fullPayload(['action' => 'draft', 'color' => 'blue']))
            ->assertRedirect();

        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertSame('blue', $draft->fresh()->color);
    }

    public function test_draft_is_editable(): void
    {
        $seller = $this->seller();
        $draft = Vehicle::create($this->fullPayload(['user_id' => $seller->id, 'status' => 'draft']));

        $this->actingAs($seller)->get(route('seller.vehicles.edit', $draft))->assertOk();
    }
}
