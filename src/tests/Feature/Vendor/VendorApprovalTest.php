<?php

namespace Tests\Feature\Vendor;

use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VendorApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $user->assignRole('admin');
        return $user;
    }

    private function makeVendor(string $status = 'pending'): Vendor
    {
        return Vendor::create([
            'name'          => 'Test Vendor',
            'slug'          => 'test-vendor-' . Str::random(4),
            'contact_email' => 'vendor@test.com',
            'status'        => $status,
        ]);
    }

    public function test_admin_can_approve_pending_vendor(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor('pending');

        $this->actingAs($admin)
            ->post(route('admin.vendors.approve', $vendor))
            ->assertRedirect();

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'status' => 'approved']);
    }

    public function test_admin_can_reject_pending_vendor_with_reason(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor('pending');

        $this->actingAs($admin)
            ->post(route('admin.vendors.reject', $vendor), [
                'reason' => 'Missing business registration certificate.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'status' => 'pending']);
    }

    public function test_reject_requires_a_reason(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor('pending');

        $this->actingAs($admin)
            ->post(route('admin.vendors.reject', $vendor), ['reason' => ''])
            ->assertSessionHasErrors('reason');
    }

    public function test_admin_can_suspend_approved_vendor(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor('approved');

        $this->actingAs($admin)
            ->post(route('admin.vendors.suspend', $vendor), [
                'reason' => 'Repeated policy violations detected.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'status' => 'suspended']);
    }

    public function test_admin_can_reactivate_suspended_vendor(): void
    {
        $admin  = $this->makeAdmin();
        $vendor = $this->makeVendor('suspended');

        $this->actingAs($admin)
            ->post(route('admin.vendors.reactivate', $vendor))
            ->assertRedirect();

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'status' => 'approved']);
    }

    public function test_customer_cannot_approve_vendor(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $customer->assignRole('customer');
        $vendor = $this->makeVendor('pending');

        $this->actingAs($customer)
            ->post(route('admin.vendors.approve', $vendor))
            ->assertStatus(403);
    }
}
