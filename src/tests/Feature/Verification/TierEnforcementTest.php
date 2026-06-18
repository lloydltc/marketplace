<?php

namespace Tests\Feature\Verification;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TierEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private VehicleMake $make;
    private VehicleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->make  = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota', 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Corolla', 'slug' => 'corolla']);
    }

    private function vehiclePayload(): array
    {
        return [
            'make_id'      => $this->make->id,
            'model_id'     => $this->model->id,
            'year'         => 2020,
            'body_type'    => 'sedan',
            'transmission' => 'manual',
            'fuel_type'    => 'petrol',
            'mileage'      => 10000,
            'color'        => 'blue',
            'condition'    => 'used',
            'price_zwl'    => 5000000.00,
        ];
    }

    private function makeVendorWithUser(string $tier = 'unverified'): array
    {
        $vendor = Vendor::create([
            'name'          => 'Dealer ' . Str::random(4),
            'slug'          => 'dealer-' . Str::random(4),
            'contact_email' => 'dealer@test.com',
            'status'        => 'approved',
            'tier'          => $tier,
        ]);

        $user = User::factory()->create(['role' => 'vendor_admin', 'email_verified_at' => now()]);
        $user->assignRole('vendor_admin');
        $user->vendors()->attach($vendor->id, [
            'vendor_role' => 'admin',
            'invited_at'  => now(),
            'joined_at'   => now(),
        ]);

        return [$vendor, $user];
    }

    private function makeSellerWithTier(string $tier = 'unverified'): User
    {
        $user = User::factory()->create([
            'role'              => 'private_seller',
            'tier'              => $tier,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('private_seller');
        return $user;
    }

    private function createVehicleForVendor(Vendor $vendor): Vehicle
    {
        return Vehicle::create(array_merge($this->vehiclePayload(), [
            'vendor_id' => $vendor->id,
            'status'    => 'pending',
        ]));
    }

    private function createVehicleForSeller(User $user): Vehicle
    {
        return Vehicle::create(array_merge($this->vehiclePayload(), [
            'user_id' => $user->id,
            'status'  => 'pending',
        ]));
    }

    // ─── Vendor enforcement ──────────────────────────────────────────────────

    public function test_unverified_vendor_blocked_after_vehicle_limit(): void
    {
        [$vendor, $user] = $this->makeVendorWithUser('unverified');
        $limit = config('tiers.limits.vendor.unverified.vehicles', 10);

        for ($i = 0; $i < $limit; $i++) {
            $this->createVehicleForVendor($vendor);
        }

        $this->actingAs($user)
            ->post(route('vendor.vehicles.store'), $this->vehiclePayload())
            ->assertRedirect()
            ->assertSessionHasErrors('listing_limit');
    }

    public function test_premium_vendor_not_blocked_at_unverified_limit(): void
    {
        [$vendor, $user] = $this->makeVendorWithUser('premium');
        $unverifiedLimit = config('tiers.limits.vendor.unverified.vehicles', 10);

        for ($i = 0; $i < $unverifiedLimit; $i++) {
            $this->createVehicleForVendor($vendor);
        }

        // Premium vendor can still create beyond the unverified limit
        $this->actingAs($user)
            ->post(route('vendor.vehicles.store'), $this->vehiclePayload())
            ->assertRedirect()
            ->assertSessionMissing('errors');
    }

    // ─── Seller enforcement ──────────────────────────────────────────────────

    public function test_unverified_seller_blocked_after_vehicle_limit(): void
    {
        $seller = $this->makeSellerWithTier('unverified');
        $limit  = config('tiers.limits.seller.unverified.vehicles', 3);

        for ($i = 0; $i < $limit; $i++) {
            $this->createVehicleForSeller($seller);
        }

        $this->actingAs($seller)
            ->post(route('seller.vehicles.store'), $this->vehiclePayload())
            ->assertRedirect()
            ->assertSessionHasErrors('listing_limit');
    }

    public function test_premium_seller_not_blocked_at_unverified_limit(): void
    {
        $seller = $this->makeSellerWithTier('premium');
        $limit  = config('tiers.limits.seller.unverified.vehicles', 3);

        for ($i = 0; $i < $limit; $i++) {
            $this->createVehicleForSeller($seller);
        }

        $this->actingAs($seller)
            ->post(route('seller.vehicles.store'), $this->vehiclePayload())
            ->assertRedirect()
            ->assertSessionMissing('errors');
    }

    // ─── Admin tier management ────────────────────────────────────────────────

    public function test_admin_can_upgrade_vendor_tier_to_premium(): void
    {
        [$vendor] = $this->makeVendorWithUser('unverified');
        $admin    = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.vendors.tier.update', $vendor), ['tier' => 'premium'])
            ->assertRedirect(route('admin.vendors.show', $vendor));

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'tier' => 'premium']);
    }

    public function test_admin_can_set_seller_tier_to_premium(): void
    {
        $seller = $this->makeSellerWithTier('unverified');
        $admin  = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.users.tier.update', $seller), ['tier' => 'premium'])
            ->assertRedirect(route('admin.users.show', $seller));

        $this->assertDatabaseHas('users', ['id' => $seller->id, 'tier' => 'premium']);
    }

    public function test_invalid_tier_value_is_rejected(): void
    {
        [$vendor] = $this->makeVendorWithUser();
        $admin    = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.vendors.tier.update', $vendor), ['tier' => 'super_duper_tier'])
            ->assertSessionHasErrors('tier');
    }
}
