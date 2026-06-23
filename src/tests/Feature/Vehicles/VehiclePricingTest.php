<?php

namespace Tests\Feature\Vehicles;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Vehicles are lead-gen, so sellers may price in USD, ZWL, or both — they are not
 * forced into ZWL. At least one currency is required.
 */
class VehiclePricingTest extends TestCase
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

    private function seller(): User
    {
        $u = User::factory()->create([
            'role' => 'private_seller', 'status' => 'active',
            'email_verified_at' => now(), 'force_password_change' => false,
        ]);
        $u->assignRole('private_seller');

        return $u;
    }

    private function payload(array $prices): array
    {
        return array_merge([
            'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2021, 'body_type' => 'pickup', 'transmission' => 'manual',
            'fuel_type' => 'diesel', 'mileage' => 20000, 'color' => 'white', 'condition' => 'used',
        ], $prices);
    }

    public function test_usd_only_vehicle_is_accepted(): void
    {
        $this->actingAs($this->seller())
            ->post(route('seller.vehicles.store'), $this->payload(['price_usd' => 15000]))
            ->assertRedirect();

        $this->assertDatabaseHas('vehicles', ['price_usd' => 15000, 'price_zwl' => null]);
    }

    public function test_zwl_only_vehicle_is_accepted(): void
    {
        $this->actingAs($this->seller())
            ->post(route('seller.vehicles.store'), $this->payload(['price_zwl' => 500000]))
            ->assertRedirect();

        $this->assertDatabaseHas('vehicles', ['price_zwl' => 500000, 'price_usd' => null]);
    }

    public function test_at_least_one_price_is_required(): void
    {
        $this->actingAs($this->seller())
            ->post(route('seller.vehicles.store'), $this->payload([]))
            ->assertSessionHasErrors(['price_zwl', 'price_usd']);
    }

    public function test_primary_price_prefers_usd_then_zwl(): void
    {
        $v = \App\Modules\Vehicles\Models\Vehicle::create($this->payload([
            'price_usd' => 9000, 'price_zwl' => 300000,
        ]) + ['user_id' => $this->seller()->id, 'status' => 'active']);

        $this->assertSame('USD 9,000.00', $v->primaryPrice());
        $this->assertSame('ZWL 300,000.00', $v->secondaryPrice());

        $usdOnly = \App\Modules\Vehicles\Models\Vehicle::create($this->payload(['price_usd' => 9000])
            + ['user_id' => $this->seller()->id, 'status' => 'active']);
        $this->assertSame('USD 9,000.00', $usdOnly->primaryPrice());
        $this->assertNull($usdOnly->secondaryPrice());
    }
}
