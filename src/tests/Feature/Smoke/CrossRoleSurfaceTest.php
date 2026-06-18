<?php

namespace Tests\Feature\Smoke;

use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\VehicleMakeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * R8/R11: functional verification that every role's key surfaces actually render
 * (no 500s, no broken route names). This is the regression net for the wiring
 * gaps the audit found (F9/F11) — "implemented" pages that never loaded for the
 * right role.
 */
class CrossRoleSurfaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->seed(CategorySeeder::class);
        $this->seed(VehicleMakeSeeder::class);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create([
            'role' => $role, 'status' => 'active',
            'email_verified_at' => now(), 'force_password_change' => false,
        ]);
        $u->assignRole($role);

        return $u;
    }

    private function vendorAdmin(): User
    {
        $vendor = Vendor::create([
            'name' => 'Smoke Vendor', 'slug' => 'smoke-' . Str::random(5),
            'contact_email' => 'smoke@x.com', 'status' => 'approved',
        ]);
        $u = $this->user('vendor_admin');
        $vendor->users()->attach($u->id, ['vendor_role' => 'admin', 'joined_at' => now()]);

        return $u;
    }

    /** GET each named route as the given user and assert it renders. */
    private function visitAll(User $user, array $routes): void
    {
        foreach ($routes as $name) {
            $this->actingAs($user)->get(route($name))
                ->assertOk(); // any 500/redirect-to-login surfaces here
        }
    }

    public function test_public_surfaces_render_for_guest(): void
    {
        foreach (['home', 'products.index', 'vehicles.index'] as $name) {
            $this->get(route($name))->assertOk();
        }
    }

    public function test_super_admin_surfaces_render(): void
    {
        $this->visitAll($this->user('super_admin'), [
            'admin.dashboard', 'admin.users.index', 'admin.users.create',
            'admin.vendors.index', 'admin.products.index', 'admin.vehicles.index',
            'admin.applications.index', 'admin.settings.index',
        ]);
    }

    public function test_admin_surfaces_render(): void
    {
        $this->visitAll($this->user('admin'), [
            'admin.dashboard', 'admin.users.index',
            'admin.vendors.index', 'admin.products.index', 'admin.vehicles.index',
            'admin.applications.index',
        ]);
    }

    public function test_vendor_admin_surfaces_render(): void
    {
        $this->visitAll($this->vendorAdmin(), [
            'vendor.dashboard', 'vendor.products.index', 'vendor.products.create',
            'vendor.vehicles.index', 'vendor.vehicles.create', 'vendor.orders.index',
            'vendor.team.index', 'vendor.wallet.show', 'vendor.profile.show',
        ]);
    }

    public function test_private_seller_surfaces_render(): void
    {
        $this->visitAll($this->user('private_seller'), [
            'seller.dashboard', 'seller.vehicles.index', 'seller.vehicles.create',
        ]);
    }

    public function test_agent_and_rider_dashboards_render(): void
    {
        $this->visitAll($this->user('agent'), ['agent.dashboard']);
        $this->visitAll($this->user('rider'), ['rider.dashboard', 'rider.deliveries.index']);
    }

    public function test_customer_buyer_surfaces_render(): void
    {
        $this->visitAll($this->user('customer'), [
            'products.index', 'vehicles.index', 'cart.index',
            'orders.index', 'rfq.index', 'saved-searches.index',
        ]);
    }
}
