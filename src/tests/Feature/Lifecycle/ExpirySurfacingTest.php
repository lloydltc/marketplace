<?php

namespace Tests\Feature\Lifecycle;

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
 * H9: lifecycle surfacing — buyer expiry countdown + seller/vendor renew prompts.
 */
class ExpirySurfacingTest extends TestCase
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

    private function vehicle(array $attrs = []): Vehicle
    {
        return Vehicle::create(array_merge([
            'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2021, 'body_type' => 'sedan', 'transmission' => 'manual', 'fuel_type' => 'petrol',
            'mileage' => 1000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 15000,
            'vehicle_type' => 'vehicle', 'status' => 'active',
        ], $attrs));
    }

    // ─── Model helpers ────────────────────────────────────────────────────────

    public function test_expiry_helpers(): void
    {
        $soon = $this->vehicle(['user_id' => $this->seller()->id, 'expires_at' => now()->addDays(3)]);
        $this->assertTrue($soon->isExpiringSoon(7));
        $this->assertFalse($soon->isExpiringSoon(2));
        $this->assertStringContainsString('Expires in', (string) $soon->expiryCountdownLabel());

        $far = $this->vehicle(['user_id' => $this->seller()->id, 'expires_at' => now()->addDays(40)]);
        $this->assertFalse($far->isExpiringSoon(7));

        $expired = $this->vehicle(['user_id' => $this->seller()->id, 'status' => 'expired', 'expires_at' => now()->subDay()]);
        $this->assertSame('Expired', $expired->expiryCountdownLabel());
        $this->assertFalse($expired->isExpiringSoon(7)); // not active
    }

    // ─── Buyer countdown ────────────────────────────────────────────────────────

    public function test_buyer_sees_countdown_when_expiring_soon(): void
    {
        $v = $this->vehicle(['user_id' => $this->seller()->id, 'expires_at' => now()->addDays(3)]);

        $this->get(route('vehicles.show', $v))
            ->assertOk()
            ->assertSee('Expires in');
    }

    public function test_buyer_sees_no_countdown_when_not_expiring_soon(): void
    {
        $v = $this->vehicle(['user_id' => $this->seller()->id, 'expires_at' => now()->addDays(40)]);

        $this->get(route('vehicles.show', $v))
            ->assertOk()
            ->assertDontSee('Expires in');
    }

    // ─── Seller renew prompt ──────────────────────────────────────────────────

    public function test_seller_dashboard_prompts_renewal_for_expiring_and_expired(): void
    {
        $seller = $this->seller();
        $this->vehicle(['user_id' => $seller->id, 'year' => 2014, 'expires_at' => now()->addDays(2)]);
        $this->vehicle(['user_id' => $seller->id, 'year' => 2013, 'status' => 'expired', 'expires_at' => now()->subDay()]);

        $this->actingAs($seller)->get(route('seller.dashboard'))
            ->assertOk()
            ->assertSee('need your attention')
            ->assertSee('2014 Toyota Hilux')
            ->assertSee('2013 Toyota Hilux')
            ->assertSee('Renew');
    }

    public function test_seller_dashboard_has_no_prompt_when_healthy(): void
    {
        $seller = $this->seller();
        $this->vehicle(['user_id' => $seller->id, 'expires_at' => now()->addDays(45)]);

        $this->actingAs($seller)->get(route('seller.dashboard'))
            ->assertOk()
            ->assertDontSee('need your attention');
    }

    public function test_seller_can_renew_from_the_prompt(): void
    {
        $seller = $this->seller();
        $v = $this->vehicle(['user_id' => $seller->id, 'status' => 'expired', 'expires_at' => now()->subDay()]);

        $this->actingAs($seller)->post(route('seller.vehicles.renew', $v))->assertRedirect();

        $this->assertSame('active', $v->fresh()->status);
        $this->assertTrue($v->fresh()->expires_at->isFuture());
    }

    // ─── Vendor renew prompt ──────────────────────────────────────────────────

    public function test_vendor_dashboard_prompts_renewal(): void
    {
        $vendor = Vendor::create(['name' => 'V Motors', 'slug' => 'v-' . Str::random(5), 'contact_email' => 'v@x.com', 'status' => 'approved']);
        $admin = User::factory()->create(['role' => 'vendor_admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $admin->assignRole('vendor_admin');
        $vendor->users()->attach($admin->id, ['vendor_role' => 'admin', 'joined_at' => now()]);

        $this->vehicle(['vendor_id' => $vendor->id, 'year' => 2012, 'expires_at' => now()->addDays(2)]);

        $this->actingAs($admin)->get(route('vendor.dashboard'))
            ->assertOk()
            ->assertSee('need your attention')
            ->assertSee('2012 Toyota Hilux');
    }
}
