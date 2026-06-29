<?php

namespace Tests\Feature\Verification;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Verification\Models\VendorVerification;
use App\Modules\Verification\Services\TierEvaluator;
use App\Notifications\VerificationExpiringNotification;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * VB2: per-dimension admin approval recomputes the badge tier (audited); the
 * maintenance command auto-demotes on expiry and sends reminders.
 */
class VerificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function admin(): User
    {
        $u = User::factory()->create(['role' => 'admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('admin');

        return $u;
    }

    private function vendor(): Vendor
    {
        return Vendor::create(['name' => 'V ' . Str::random(4), 'slug' => 'v-' . Str::random(6), 'contact_email' => Str::random(5) . '@x.com', 'status' => 'approved']);
    }

    public function test_admin_approving_dimensions_earns_a_tier_and_audits(): void
    {
        $vendor = $this->vendor();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.vendors.verifications.update', [$vendor, 'company_reg']), ['status' => 'approved'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.vendors.verifications.update', [$vendor, 'banking']), ['status' => 'approved'])->assertRedirect();

        $this->assertSame('verified_dealer', $vendor->fresh()->verification_tier);
        $this->assertDatabaseHas('vendor_verifications', ['vendor_id' => $vendor->id, 'dimension' => 'banking', 'status' => 'approved']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'verification.dimension.approved', 'target_id' => $vendor->id]);
    }

    public function test_approval_sets_expiry_from_config(): void
    {
        config(['verification.dimension_expiry_months' => 12]);
        $vendor = $this->vendor();

        $this->actingAs($this->admin())->post(route('admin.vendors.verifications.update', [$vendor, 'tax']), ['status' => 'approved']);

        $row = VendorVerification::where('vendor_id', $vendor->id)->where('dimension', 'tax')->first();
        $this->assertNotNull($row->expires_at);
        $this->assertTrue($row->expires_at->between(now()->addMonths(12)->subDay(), now()->addMonths(12)->addDay()));
    }

    public function test_invalid_dimension_is_404(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.vendors.verifications.update', [$this->vendor(), 'not_a_dimension']), ['status' => 'approved'])
            ->assertNotFound();
    }

    public function test_non_admin_cannot_verify(): void
    {
        $seller = User::factory()->create(['role' => 'private_seller', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $seller->assignRole('private_seller');

        $this->actingAs($seller)
            ->post(route('admin.vendors.verifications.update', [$this->vendor(), 'company_reg']), ['status' => 'approved'])
            ->assertForbidden();
    }

    public function test_maintain_command_auto_demotes_on_expiry(): void
    {
        $vendor = $this->vendor();
        VendorVerification::create(['vendor_id' => $vendor->id, 'dimension' => 'company_reg', 'status' => 'approved', 'verified_at' => now(), 'expires_at' => now()->addMonths(12)]);
        VendorVerification::create(['vendor_id' => $vendor->id, 'dimension' => 'banking', 'status' => 'approved', 'verified_at' => now(), 'expires_at' => now()->addMonths(12)]);
        app(TierEvaluator::class)->recompute($vendor);
        $this->assertSame('verified_dealer', $vendor->fresh()->verification_tier);

        // Expire one required dimension, then run upkeep.
        $vendor->verifications()->where('dimension', 'company_reg')->update(['expires_at' => now()->subDay()]);
        $this->artisan('verification:maintain')->assertExitCode(0);

        $this->assertNull($vendor->fresh()->verification_tier);
    }

    public function test_maintain_command_reminds_before_expiry(): void
    {
        Notification::fake();
        $vendor = $this->vendor();
        $owner = User::factory()->create(['role' => 'vendor_admin', 'status' => 'active']);
        $vendor->users()->attach($owner->id, ['vendor_role' => 'admin', 'joined_at' => now()]);
        VendorVerification::create(['vendor_id' => $vendor->id, 'dimension' => 'company_reg', 'status' => 'approved', 'verified_at' => now(), 'expires_at' => now()->addDays(7)]);

        $this->artisan('verification:maintain', ['--remind-days' => 14]);

        Notification::assertSentTo($owner, VerificationExpiringNotification::class);
    }
}
