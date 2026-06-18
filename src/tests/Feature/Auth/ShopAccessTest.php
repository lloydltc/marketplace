<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R1 + P1 — server-side enforcement of the role access matrix. Hiding a menu item
 * is never enough; non-customer roles must be rejected from buyer surfaces by URL.
 * Corrected model (P1): a SELLER IS NOT A CUSTOMER — private_seller and vendor
 * roles get NO buyer surfaces, only `customer` (and guests) do.
 */
class ShopAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function user(string $role): User
    {
        /** @var User $u */
        $u = User::factory()->create(['role' => $role, 'email_verified_at' => now()]);
        $u->assignRole($role);
        return $u;
    }

    /**
     * Every non-customer role — none of these may reach buyer surfaces.
     * @return string[]
     */
    private function staffRoles(): array
    {
        return ['admin', 'super_admin', 'agent', 'vendor_admin', 'vendor_worker', 'private_seller', 'rider'];
    }

    public function test_staff_cannot_access_cart(): void
    {
        foreach ($this->staffRoles() as $role) {
            $this->actingAs($this->user($role))->get(route('cart.index'))
                ->assertForbidden();
        }
    }

    public function test_staff_cannot_access_buyer_orders(): void
    {
        foreach ($this->staffRoles() as $role) {
            $this->actingAs($this->user($role))->get(route('orders.index'))
                ->assertForbidden();
        }
    }

    public function test_staff_cannot_access_rfq_or_saved_searches(): void
    {
        foreach ($this->staffRoles() as $role) {
            $actor = $this->user($role);
            $this->actingAs($actor)->get(route('rfq.index'))->assertForbidden();
            $this->actingAs($actor)->get(route('saved-searches.index'))->assertForbidden();
        }
    }

    public function test_only_guest_and_customer_can_reach_cart(): void
    {
        $this->get(route('cart.index'))->assertOk(); // guest
        $this->actingAs($this->user('customer'))->get(route('cart.index'))->assertOk();
        // Sellers are NOT customers — rejected (P1).
        $this->actingAs($this->user('private_seller'))->get(route('cart.index'))->assertForbidden();
        $this->actingAs($this->user('vendor_admin'))->get(route('cart.index'))->assertForbidden();
    }

    public function test_customer_can_reach_buyer_surfaces(): void
    {
        $customer = $this->user('customer');
        $this->actingAs($customer)->get(route('orders.index'))->assertOk();
        $this->actingAs($customer)->get(route('rfq.index'))->assertOk();
        $this->actingAs($customer)->get(route('saved-searches.index'))->assertOk();
    }

    public function test_platform_settings_is_super_admin_only(): void
    {
        $this->actingAs($this->user('admin'))->get(route('admin.settings.index'))->assertForbidden();
        $this->actingAs($this->user('super_admin'))->get(route('admin.settings.index'))->assertOk();
    }
}
