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
 * VB5: tier badge surfacing (storefront), vendor progress view, admin panel.
 */
class BadgeSurfacingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function premiumDealer(): Vendor
    {
        $vendor = Vendor::create(['name' => 'Premium Motors', 'slug' => 'pm-' . Str::random(6), 'contact_email' => Str::random(5) . '@x.com', 'status' => 'approved']);
        foreach (['company_reg', 'tax', 'banking', 'location'] as $d) {
            VendorVerification::create(['vendor_id' => $vendor->id, 'dimension' => $d, 'status' => 'approved', 'verified_at' => now()]);
        }
        app(TierEvaluator::class)->recompute($vendor);

        return $vendor->fresh();
    }

    public function test_tier_badge_renders_on_dealer_storefront(): void
    {
        $vendor = $this->premiumDealer();
        $this->assertSame('premium_dealer', $vendor->verification_tier);

        $this->get(route('dealers.show', $vendor->slug))
            ->assertOk()
            ->assertSee('Premium Dealer'); // icon+label, never colour-only
    }

    public function test_directory_card_shows_tier_badge(): void
    {
        $this->premiumDealer();

        $this->get(route('dealers.index'))
            ->assertOk()
            ->assertSee('Premium Dealer');
    }

    public function test_vendor_progress_view_shows_dimensions_and_next_tier(): void
    {
        $vendor = Vendor::create(['name' => 'V', 'slug' => 'v-' . Str::random(6), 'contact_email' => 'v@x.com', 'status' => 'approved']);
        VendorVerification::create(['vendor_id' => $vendor->id, 'dimension' => 'company_reg', 'status' => 'approved', 'verified_at' => now()]);

        $admin = User::factory()->create(['role' => 'vendor_admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $admin->assignRole('vendor_admin');
        $vendor->users()->attach($admin->id, ['vendor_role' => 'admin', 'joined_at' => now()]);

        $this->actingAs($admin)->get(route('vendor.verification.show'))
            ->assertOk()
            ->assertSee('Verification &amp; badges', false)
            ->assertSee('Next:')           // shows the next tier to earn
            ->assertSee('Verify');         // a missing dimension instruction
    }

    public function test_admin_vendor_page_has_badge_management_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $admin->assignRole('admin');
        $vendor = $this->premiumDealer();

        $this->actingAs($admin)->get(route('admin.vendors.show', $vendor->id))
            ->assertOk()
            ->assertSee('Trust badge &amp; verification', false)
            ->assertSee('Revoke badge');
    }
}
