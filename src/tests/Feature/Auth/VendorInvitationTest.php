<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VendorInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function createVendorAdmin(): array
    {
        $vendor = Vendor::create([
            'id'            => (string) Str::uuid(),
            'name'          => 'Test Vendor',
            'slug'          => 'test-vendor',
            'contact_email' => 'vendor@example.com',
        ]);

        $user = User::factory()->create([
            'role'              => 'vendor_admin',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('vendor_admin');

        $vendor->users()->attach($user->id, ['vendor_role' => 'admin', 'invited_at' => now(), 'joined_at' => now()]);

        return [$vendor, $user];
    }

    public function test_vendor_admin_can_send_invitation(): void
    {
        [$vendor, $admin] = $this->createVendorAdmin();

        $this->actingAs($admin)
            ->post('/vendor/invite', [
                'email'         => 'worker@example.com',
                'temp_password' => 'TempPass@99',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('vendor_invitations', [
            'vendor_id' => $vendor->id,
            'email'     => 'worker@example.com',
        ]);
    }

    public function test_accept_with_valid_token_creates_worker_account(): void
    {
        [$vendor, $admin] = $this->createVendorAdmin();

        $invitation = VendorInvitation::create([
            'id'            => (string) Str::uuid(),
            'vendor_id'     => $vendor->id,
            'invited_by'    => $admin->id,
            'email'         => 'worker@example.com',
            'temp_password' => 'TempPass@99',
            'token'         => 'valid-token-123',
            'expires_at'    => now()->addHours(48),
        ]);

        $this->post("/vendor/invite/{$invitation->token}", ['name' => 'New Worker'])
            ->assertRedirect('/change-password');

        $this->assertDatabaseHas('users', [
            'email'                 => 'worker@example.com',
            'role'                  => 'vendor_worker',
            'force_password_change' => true,
        ]);
    }

    public function test_expired_token_is_rejected_on_accept_page(): void
    {
        [$vendor, $admin] = $this->createVendorAdmin();

        $invitation = VendorInvitation::create([
            'id'            => (string) Str::uuid(),
            'vendor_id'     => $vendor->id,
            'invited_by'    => $admin->id,
            'email'         => 'worker@example.com',
            'temp_password' => 'TempPass@99',
            'token'         => 'expired-token',
            'expires_at'    => now()->subHour(),
        ]);

        $this->get("/vendor/invite/{$invitation->token}")
            ->assertSee('not found');
    }

    public function test_force_password_change_is_triggered_on_new_worker(): void
    {
        [$vendor, $admin] = $this->createVendorAdmin();

        $invitation = VendorInvitation::create([
            'id'            => (string) Str::uuid(),
            'vendor_id'     => $vendor->id,
            'invited_by'    => $admin->id,
            'email'         => 'worker2@example.com',
            'temp_password' => 'TempPass@99',
            'token'         => 'force-pass-token',
            'expires_at'    => now()->addHours(48),
        ]);

        $this->post("/vendor/invite/{$invitation->token}", ['name' => 'Worker Two']);

        $worker = User::where('email', 'worker2@example.com')->firstOrFail();
        $this->assertTrue($worker->force_password_change);
    }
}
