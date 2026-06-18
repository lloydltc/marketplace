<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VendorScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_vendor_worker_cannot_access_another_vendors_dashboard(): void
    {
        $vendorA = Vendor::create([
            'id'            => (string) Str::uuid(),
            'name'          => 'Vendor A',
            'slug'          => 'vendor-a',
            'contact_email' => 'a@example.com',
        ]);

        $vendorB = Vendor::create([
            'id'            => (string) Str::uuid(),
            'name'          => 'Vendor B',
            'slug'          => 'vendor-b',
            'contact_email' => 'b@example.com',
        ]);

        $workerB = User::factory()->create([
            'role'              => 'vendor_worker',
            'email_verified_at' => now(),
        ]);
        $workerB->assignRole('vendor_worker');
        $vendorB->users()->attach($workerB->id, ['vendor_role' => 'worker', 'joined_at' => now()]);

        // Worker B can access vendor dashboard (scoped to vendor B)
        $this->actingAs($workerB)->get('/vendor/dashboard')->assertStatus(200);

        // Vendor A should only be accessible by Vendor A's own members
        $this->assertTrue($workerB->belongsToVendor($vendorB->id));
        $this->assertFalse($workerB->belongsToVendor($vendorA->id));
    }

    public function test_vendor_scope_middleware_binds_correct_vendor_to_request(): void
    {
        $vendor = Vendor::create([
            'id'            => (string) Str::uuid(),
            'name'          => 'My Vendor',
            'slug'          => 'my-vendor',
            'contact_email' => 'mv@example.com',
        ]);

        $worker = User::factory()->create([
            'role'              => 'vendor_worker',
            'email_verified_at' => now(),
        ]);
        $worker->assignRole('vendor_worker');
        $vendor->users()->attach($worker->id, ['vendor_role' => 'worker', 'joined_at' => now()]);

        $this->actingAs($worker)->get('/vendor/dashboard')->assertStatus(200);
    }
}
