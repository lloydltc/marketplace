<?php

namespace Tests\Feature\Vendor;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * R5: vendor_admin team management, scoped server-side to the admin's own vendor.
 */
class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function vendor(string $name): Vendor
    {
        return Vendor::create([
            'name'          => $name,
            'slug'          => Str::slug($name) . '-' . Str::random(4),
            'contact_email' => Str::random(6) . '@x.com',
            'status'        => 'approved',
        ]);
    }

    private function member(Vendor $vendor, string $role): User
    {
        $globalRole = $role === 'admin' ? 'vendor_admin' : 'vendor_worker';
        $user = User::factory()->create([
            'role'                  => $globalRole,
            'status'                => 'active',
            'email_verified_at'     => now(),
            'force_password_change' => false,
        ]);
        $user->assignRole($globalRole);
        $vendor->users()->attach($user->id, ['vendor_role' => $role, 'joined_at' => now()]);

        return $user;
    }

    public function test_admin_sees_only_own_team(): void
    {
        $vendorA = $this->vendor('Alpha');
        $adminA  = $this->member($vendorA, 'admin');
        $workerA = $this->member($vendorA, 'worker');

        $vendorB = $this->vendor('Bravo');
        $workerB = $this->member($vendorB, 'worker');

        $this->actingAs($adminA)->get(route('vendor.team.index'))
            ->assertOk()
            ->assertSee($workerA->name)
            ->assertDontSee($workerB->name);
    }

    public function test_admin_cannot_modify_another_vendors_member(): void
    {
        $vendorA = $this->vendor('Alpha');
        $adminA  = $this->member($vendorA, 'admin');

        $vendorB = $this->vendor('Bravo');
        $workerB = $this->member($vendorB, 'worker');

        // Cross-vendor role change is rejected (404 — not a member of admin A's vendor).
        $this->actingAs($adminA)
            ->put(route('vendor.team.role', $workerB), ['vendor_role' => 'admin'])
            ->assertNotFound();

        $this->actingAs($adminA)
            ->delete(route('vendor.team.remove', $workerB))
            ->assertNotFound();

        $this->assertSame('worker', $workerB->fresh()->pivotRoleFor($vendorB));
    }

    public function test_admin_can_promote_and_remove_own_member(): void
    {
        $vendor  = $this->vendor('Alpha');
        $admin   = $this->member($vendor, 'admin');
        $worker  = $this->member($vendor, 'worker');

        $this->actingAs($admin)
            ->put(route('vendor.team.role', $worker), ['vendor_role' => 'admin'])
            ->assertRedirect();
        $this->assertSame('admin', $worker->fresh()->pivotRoleFor($vendor));
        $this->assertTrue($worker->fresh()->hasRole('vendor_admin'));

        $this->actingAs($admin)
            ->delete(route('vendor.team.remove', $worker))
            ->assertRedirect();
        $this->assertFalse($vendor->users()->where('users.id', $worker->id)->exists());
    }

    public function test_cannot_demote_or_remove_last_admin(): void
    {
        $vendor = $this->vendor('Alpha');
        $admin  = $this->member($vendor, 'admin');

        $this->actingAs($admin)
            ->put(route('vendor.team.role', $admin), ['vendor_role' => 'worker'])
            ->assertSessionHasErrors('team');
        $this->assertSame('admin', $admin->fresh()->pivotRoleFor($vendor));
    }

    public function test_privileged_action_is_audit_logged(): void
    {
        $vendor = $this->vendor('Alpha');
        $admin  = $this->member($vendor, 'admin');
        $worker = $this->member($vendor, 'worker');

        $this->actingAs($admin)
            ->delete(route('vendor.team.remove', $worker));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action'   => 'vendor.member.remove',
            'target_id' => $worker->id,
        ]);
    }

    public function test_worker_cannot_reach_team_management(): void
    {
        $vendor = $this->vendor('Alpha');
        $worker = $this->member($vendor, 'worker');

        $this->actingAs($worker)->get(route('vendor.team.index'))->assertForbidden();
    }
}
