<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function makeVerifiedUser(string $role): User
    {
        $user = User::factory()->create([
            'role'              => $role,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role);
        return $user;
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = $this->makeVerifiedUser('admin');
        $this->actingAs($admin)->get('/admin/dashboard')->assertStatus(200);
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $customer = $this->makeVerifiedUser('customer');
        $this->actingAs($customer)->get('/admin/dashboard')->assertStatus(403);
    }

    public function test_vendor_admin_can_access_vendor_dashboard(): void
    {
        $vendorAdmin = $this->makeVerifiedUser('vendor_admin');
        $this->actingAs($vendorAdmin)->get('/vendor/dashboard')->assertStatus(200);
    }

    public function test_customer_cannot_access_vendor_dashboard(): void
    {
        $customer = $this->makeVerifiedUser('customer');
        $this->actingAs($customer)->get('/vendor/dashboard')->assertStatus(403);
    }

    public function test_agent_can_access_agent_dashboard(): void
    {
        $agent = $this->makeVerifiedUser('agent');
        $this->actingAs($agent)->get('/agent/dashboard')->assertStatus(200);
    }

    public function test_vendor_cannot_access_agent_dashboard(): void
    {
        $vendor = $this->makeVerifiedUser('vendor_admin');
        $this->actingAs($vendor)->get('/agent/dashboard')->assertStatus(403);
    }

    public function test_private_seller_can_access_seller_dashboard(): void
    {
        $seller = $this->makeVerifiedUser('private_seller');
        $this->actingAs($seller)->get('/seller/dashboard')->assertStatus(200);
    }

    public function test_customer_cannot_access_seller_dashboard(): void
    {
        $customer = $this->makeVerifiedUser('customer');
        $this->actingAs($customer)->get('/seller/dashboard')->assertStatus(403);
    }
}
