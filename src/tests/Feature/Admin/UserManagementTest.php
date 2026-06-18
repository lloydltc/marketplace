<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R6: super-admin user management. Destructive actions are super_admin-only
 * (plain admins are read-only) and every action is audit-logged.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function staff(string $role): User
    {
        $user = User::factory()->create([
            'role'                  => $role,
            'status'                => 'active',
            'email_verified_at'     => now(),
            'force_password_change' => false,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function target(string $role = 'customer'): User
    {
        $user = User::factory()->create([
            'role'                  => $role,
            'status'                => 'active',
            'email_verified_at'     => now(),
            'force_password_change' => false,
        ]);
        $user->assignRole($role);

        return $user;
    }

    // ─── RBAC: only super_admin may manage ─────────────────────────────────────────

    public function test_plain_admin_cannot_suspend_user(): void
    {
        $admin  = $this->staff('admin');
        $target = $this->target();

        $this->actingAs($admin)
            ->post(route('admin.users.suspend', $target))
            ->assertForbidden();

        $this->assertSame('active', $target->fresh()->status);
    }

    public function test_plain_admin_can_still_view_users(): void
    {
        $admin = $this->staff('admin');

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.show', $this->target()))->assertOk();
    }

    public function test_plain_admin_cannot_reach_create_form(): void
    {
        $this->actingAs($this->staff('admin'))
            ->get(route('admin.users.create'))
            ->assertForbidden();
    }

    // ─── super_admin actions ────────────────────────────────────────────────────────

    public function test_super_admin_can_suspend_and_reactivate(): void
    {
        $super  = $this->staff('super_admin');
        $target = $this->target();

        $this->actingAs($super)->post(route('admin.users.suspend', $target))->assertRedirect();
        $this->assertSame('suspended', $target->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.suspend', 'target_id' => $target->id]);

        $this->actingAs($super)->post(route('admin.users.reactivate', $target))->assertRedirect();
        $this->assertSame('active', $target->fresh()->status);
    }

    public function test_super_admin_can_change_role(): void
    {
        $super  = $this->staff('super_admin');
        $target = $this->target('customer');

        $this->actingAs($super)
            ->put(route('admin.users.role', $target), ['role' => 'agent'])
            ->assertRedirect();

        $fresh = $target->fresh();
        $this->assertSame('agent', $fresh->role);
        $this->assertTrue($fresh->hasRole('agent'));
        $this->assertFalse($fresh->hasRole('customer'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.role_change', 'target_id' => $target->id]);
    }

    public function test_super_admin_can_reset_password_and_force_change(): void
    {
        $super  = $this->staff('super_admin');
        $target = $this->target();
        $oldHash = $target->password;

        $this->actingAs($super)->post(route('admin.users.reset-password', $target))->assertRedirect();

        $fresh = $target->fresh();
        $this->assertNotSame($oldHash, $fresh->password);
        $this->assertTrue($fresh->force_password_change);
    }

    public function test_super_admin_can_bypass_email_verification(): void
    {
        $super  = $this->staff('super_admin');
        $target = User::factory()->unverified()->create(['status' => 'active', 'force_password_change' => false]);
        $target->assignRole('customer');

        $this->actingAs($super)->post(route('admin.users.verify-email', $target))->assertRedirect();
        $this->assertNotNull($target->fresh()->email_verified_at);
    }

    public function test_super_admin_can_create_user(): void
    {
        $super = $this->staff('super_admin');

        $this->actingAs($super)->post(route('admin.users.store'), [
            'name'  => 'New Agent',
            'email' => 'new.agent@example.com',
            'role'  => 'agent',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'new.agent@example.com', 'role' => 'agent']);
        $created = User::where('email', 'new.agent@example.com')->first();
        $this->assertTrue($created->force_password_change);
        $this->assertTrue($created->hasRole('agent'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.create', 'target_id' => $created->id]);
    }

    // ─── Guard rails ──────────────────────────────────────────────────────────────

    public function test_super_admin_cannot_suspend_self(): void
    {
        $super = $this->staff('super_admin');

        $this->actingAs($super)
            ->post(route('admin.users.suspend', $super))
            ->assertSessionHasErrors('user');
        $this->assertSame('active', $super->fresh()->status);
    }

    public function test_suspended_user_is_logged_out_on_next_request(): void
    {
        $target = $this->target('agent');
        $target->update(['status' => 'suspended']);

        $this->actingAs($target)
            ->get(route('agent.dashboard'))
            ->assertRedirect(route('login'));
    }
}
