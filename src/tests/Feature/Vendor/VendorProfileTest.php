<?php

namespace Tests\Feature\Vendor;

use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VendorProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeVendorAdmin(): array
    {
        $vendor = Vendor::create([
            'name'          => 'My Vendor',
            'slug'          => 'my-vendor',
            'contact_email' => 'vendor@test.com',
            'status'        => 'approved',
        ]);

        $user = User::factory()->create(['role' => 'vendor_admin', 'email_verified_at' => now()]);
        $user->assignRole('vendor_admin');
        $vendor->users()->attach($user->id, ['vendor_role' => 'admin', 'joined_at' => now()]);

        return [$vendor, $user];
    }

    public function test_vendor_admin_can_view_profile(): void
    {
        [$vendor, $user] = $this->makeVendorAdmin();

        $this->actingAs($user)->get(route('vendor.profile.show'))->assertStatus(200);
    }

    public function test_vendor_admin_can_update_profile(): void
    {
        [$vendor, $user] = $this->makeVendorAdmin();

        $this->actingAs($user)
            ->put(route('vendor.profile.update'), [
                'name'          => 'Updated Business Name',
                'contact_email' => 'updated@test.com',
            ])
            ->assertRedirect(route('vendor.profile.show'));

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'name' => 'Updated Business Name']);
    }

    public function test_vendor_worker_cannot_edit_profile(): void
    {
        [$vendor, ] = $this->makeVendorAdmin();

        $worker = User::factory()->create(['role' => 'vendor_worker', 'email_verified_at' => now()]);
        $worker->assignRole('vendor_worker');
        $vendor->users()->attach($worker->id, ['vendor_role' => 'worker', 'joined_at' => now()]);

        $this->actingAs($worker)->get(route('vendor.profile.edit'))->assertStatus(403);
    }

    public function test_profile_update_validates_email(): void
    {
        [$vendor, $user] = $this->makeVendorAdmin();

        $this->actingAs($user)
            ->put(route('vendor.profile.update'), ['contact_email' => 'not-an-email'])
            ->assertSessionHasErrors('contact_email');
    }
}
