<?php

namespace Tests\Feature\Vehicles;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use App\Modules\Vehicles\Services\VehicleService;
use App\Notifications\VehicleListingNotification;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * D5: vehicle listing expiry & renewal — approval starts the clock, the sweep job
 * expires lapsed listings (and hides them publicly), sellers renew, and reminders
 * fire before expiry.
 */
class VehicleLifecycleTest extends TestCase
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

    private function vehicle(User $seller, array $attrs = []): Vehicle
    {
        return Vehicle::create(array_merge([
            'user_id' => $seller->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2021, 'body_type' => 'pickup', 'transmission' => 'manual', 'fuel_type' => 'diesel',
            'mileage' => 1000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 20000, 'status' => 'active',
        ], $attrs));
    }

    public function test_approval_sets_published_and_expiry(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);
        $vehicle = $this->vehicle($this->seller(), ['status' => 'pending']);

        app(VehicleService::class)->approve($vehicle, $admin);

        $vehicle->refresh();
        $this->assertNotNull($vehicle->published_at);
        $this->assertNotNull($vehicle->expires_at);
        $this->assertEqualsWithDelta(60, now()->diffInDays($vehicle->expires_at, false), 1);
    }

    public function test_expire_command_lapses_and_notifies(): void
    {
        Notification::fake();
        $seller = $this->seller();
        $lapsed = $this->vehicle($seller, ['expires_at' => now()->subDay()]);
        $live   = $this->vehicle($seller, ['expires_at' => now()->addDays(10)]);

        $this->artisan('vehicles:expire')->assertExitCode(0);

        $this->assertSame('expired', $lapsed->fresh()->status);
        $this->assertSame('active', $live->fresh()->status);
        Notification::assertSentTo($seller, VehicleListingNotification::class);
    }

    public function test_expired_listing_is_hidden_from_public_catalogue(): void
    {
        $seller = $this->seller();
        $expired = $this->vehicle($seller, ['status' => 'expired', 'year' => 2021]);
        $live    = $this->vehicle($seller, ['status' => 'active', 'year' => 2019, 'expires_at' => now()->addDays(5)]);

        $this->get(route('vehicles.index'))
            ->assertOk()
            ->assertSee('2019 Toyota Hilux')
            ->assertDontSee('2021 Toyota Hilux');
    }

    public function test_lapsed_but_unswept_listing_is_also_hidden(): void
    {
        $seller = $this->seller();
        // still 'active' but expiry passed (job hasn't run yet) → must not show.
        $this->vehicle($seller, ['status' => 'active', 'expires_at' => now()->subHour()]);

        $this->get(route('vehicles.index'))->assertOk()->assertDontSee('Toyota Hilux');
    }

    public function test_seller_can_renew_expired_listing(): void
    {
        $seller = $this->seller();
        $vehicle = $this->vehicle($seller, ['status' => 'expired', 'expires_at' => now()->subDay(), 'expiry_count' => 0]);

        $this->actingAs($seller)->post(route('seller.vehicles.renew', $vehicle))->assertRedirect();

        $vehicle->refresh();
        $this->assertSame('active', $vehicle->status);
        $this->assertTrue($vehicle->expires_at->isFuture());
        $this->assertNotNull($vehicle->renewed_at);
        $this->assertSame(1, $vehicle->expiry_count);
    }

    public function test_non_owner_cannot_renew(): void
    {
        $vehicle = $this->vehicle($this->seller(), ['status' => 'expired']);
        $this->actingAs($this->seller())->post(route('seller.vehicles.renew', $vehicle))->assertForbidden();
    }

    public function test_reminders_fire_for_listings_nearing_expiry(): void
    {
        Notification::fake();
        $seller = $this->seller();
        $this->vehicle($seller, ['expires_at' => now()->addDays(7)->setTime(12, 0)]); // 7-day window
        $this->vehicle($seller, ['expires_at' => now()->addDays(30)]);                 // not yet

        $this->artisan('vehicles:expiry-reminders')->assertExitCode(0);

        Notification::assertSentTo($seller, VehicleListingNotification::class,
            fn ($n) => $n->kind === 'expiring');
    }
}
