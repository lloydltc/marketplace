<?php

namespace Tests\Feature\Vehicles;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * H2: Zimbabwe-market fields — POA (hide price), duty paid, recent import,
 * ref code, steering — persist on save and surface on cards + detail.
 */
class ZimbabweFieldsTest extends TestCase
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

    private function payload(array $extra = []): array
    {
        return array_merge([
            'action' => 'publish', 'vehicle_type' => 'vehicle',
            'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2021, 'body_type' => 'sedan', 'transmission' => 'manual',
            'fuel_type' => 'petrol', 'mileage' => 1000, 'color' => 'white',
            'condition' => 'used', 'price_usd' => 15000,
        ], $extra);
    }

    public function test_zimbabwe_fields_persist(): void
    {
        $this->actingAs($this->seller())->post(route('seller.vehicles.store'), $this->payload([
            'show_price' => '0', 'duty_paid' => '1', 'is_recent_import' => '1',
            'ref_code' => 'REF-9981', 'steering' => 'lhd',
        ]))->assertRedirect();

        $this->assertDatabaseHas('vehicles', [
            'show_price' => false, 'duty_paid' => true, 'is_recent_import' => true,
            'ref_code' => 'REF-9981', 'steering' => 'lhd',
        ]);
    }

    public function test_poa_hides_price_everywhere(): void
    {
        $v = Vehicle::create($this->payload(['user_id' => $this->seller()->id, 'status' => 'active', 'price_usd' => 15000, 'show_price' => false]) + ['expires_at' => now()->addDays(10)]);

        $this->assertSame('Price on application', $v->primaryPrice());
        $this->assertNull($v->secondaryPrice());

        $this->get(route('vehicles.index'))->assertOk()->assertSee('Price on application');
        $this->get(route('vehicles.show', $v))->assertOk()
            ->assertSee('Price on application')
            ->assertDontSee('USD 15,000');
    }

    public function test_recent_import_and_steering_show_on_detail(): void
    {
        $v = Vehicle::create($this->payload([
            'user_id' => $this->seller()->id, 'status' => 'active',
            'is_recent_import' => true, 'duty_paid' => true, 'steering' => 'rhd', 'ref_code' => 'AB12',
        ]) + ['expires_at' => now()->addDays(10)]);

        $this->get(route('vehicles.show', $v))->assertOk()
            ->assertSee('Recent import')
            ->assertSee('Duty paid')
            ->assertSee('AB12');
    }

    public function test_invalid_steering_rejected(): void
    {
        $this->actingAs($this->seller())->post(route('seller.vehicles.store'), $this->payload(['steering' => 'sideways']))
            ->assertSessionHasErrors('steering');
    }
}
