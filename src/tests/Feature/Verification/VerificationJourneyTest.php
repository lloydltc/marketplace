<?php

namespace Tests\Feature\Verification;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Verification\Models\VendorVerification;
use App\Modules\Verification\Services\TierEvaluator;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * VB6: end-to-end gate — a vendor earns a tier through admin verification, the
 * badge renders publicly, expiry demotes it, revocation suppresses it, and every
 * action is audited. Tier rules are config-driven.
 */
class VerificationJourneyTest extends TestCase
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

    public function test_full_journey_earn_render_demote_revoke(): void
    {
        $admin = $this->admin();
        $vendor = Vendor::create(['name' => 'Journey Motors', 'slug' => 'jm-' . Str::random(6), 'contact_email' => 'jm@x.com', 'status' => 'approved']);

        // Earn Verified Dealer (company_reg + banking) via the admin endpoints.
        foreach (['company_reg', 'banking'] as $dim) {
            $this->actingAs($admin)->post(route('admin.vendors.verifications.update', [$vendor, $dim]), ['status' => 'approved'])->assertRedirect();
        }
        $this->assertSame('verified_dealer', $vendor->fresh()->verification_tier);

        // Earn Premium Dealer (+ tax + location).
        foreach (['tax', 'location'] as $dim) {
            $this->actingAs($admin)->post(route('admin.vendors.verifications.update', [$vendor, $dim]), ['status' => 'approved'])->assertRedirect();
        }
        $this->assertSame('premium_dealer', $vendor->fresh()->verification_tier);

        // Badge renders publicly.
        $this->get(route('dealers.show', $vendor->slug))->assertOk()->assertSee('Premium Dealer');

        // A required dimension expires → maintenance demotes to Verified Dealer.
        $vendor->verifications()->where('dimension', 'location')->update(['expires_at' => now()->subDay()]);
        $this->artisan('verification:maintain')->assertExitCode(0);
        $this->assertSame('verified_dealer', $vendor->fresh()->verification_tier);

        // Revoke → no badge anywhere.
        $this->actingAs($admin)->post(route('admin.vendors.badge.update', $vendor), ['action' => 'revoke', 'reason' => 'Investigation'])->assertRedirect();
        $this->assertNull($vendor->fresh()->verification_tier);
        $this->get(route('dealers.show', $vendor->slug))->assertOk()->assertDontSee('Premium Dealer');

        // Everything audited.
        $this->assertDatabaseHas('audit_logs', ['action' => 'verification.dimension.approved', 'target_id' => $vendor->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'badge.revoke', 'target_id' => $vendor->id]);
    }

    public function test_tier_rules_are_config_driven(): void
    {
        // Redefine Verified Dealer to need only identity, and confirm the evaluator obeys.
        config(['verification.tiers.verified_dealer.required_dimensions' => ['identity']]);

        $vendor = Vendor::create(['name' => 'Cfg', 'slug' => 'cfg-' . Str::random(6), 'contact_email' => 'c@x.com', 'status' => 'approved']);
        VendorVerification::create(['vendor_id' => $vendor->id, 'dimension' => 'identity', 'status' => 'approved', 'verified_at' => now()]);

        $this->assertContains('verified_dealer', app(TierEvaluator::class)->earnedTiers($vendor->fresh()));
    }
}
