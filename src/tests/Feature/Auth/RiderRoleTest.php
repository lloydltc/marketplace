<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiderRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeVerifiedUser(string $role): User
    {
        /** @var User $user */
        $user = User::factory()->create([
            'role'              => $role,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role);
        return $user;
    }

    public function test_rider_role_can_be_assigned_and_authenticate(): void
    {
        $rider = $this->makeVerifiedUser('rider');

        $this->assertTrue($rider->hasRole('rider'));
        $this->assertDatabaseHas('users', ['id' => $rider->id, 'role' => 'rider']);
    }

    public function test_rider_can_access_rider_dashboard(): void
    {
        $rider = $this->makeVerifiedUser('rider');
        $this->actingAs($rider)->get('/rider/dashboard')->assertStatus(200);
    }

    public function test_rider_cannot_access_admin_dashboard(): void
    {
        $rider = $this->makeVerifiedUser('rider');
        $this->actingAs($rider)->get('/admin/dashboard')->assertStatus(403);
    }

    public function test_rider_cannot_access_vendor_dashboard(): void
    {
        $rider = $this->makeVerifiedUser('rider');
        $this->actingAs($rider)->get('/vendor/dashboard')->assertStatus(403);
    }

    public function test_customer_cannot_access_rider_dashboard(): void
    {
        $customer = $this->makeVerifiedUser('customer');
        $this->actingAs($customer)->get('/rider/dashboard')->assertStatus(403);
    }

    public function test_rider_has_expected_permissions(): void
    {
        $rider = $this->makeVerifiedUser('rider');

        $this->assertTrue($rider->can('view-assigned-deliveries'));
        $this->assertTrue($rider->can('update-delivery-status'));
        $this->assertTrue($rider->can('record-cod-collection'));
        $this->assertFalse($rider->can('manage-vendors'));
    }
}
