<?php

namespace Tests\Feature\Verification;

use App\Models\ListingReport;
use App\Models\User;
use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Products\Models\Product;
use App\Modules\Verification\Models\VendorVerification;
use App\Modules\Verification\Services\FraudRuleService;
use App\Modules\Verification\Services\TierEvaluator;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * VB4: badge revocation/reinstatement + manual grant; deterministic fraud rules
 * (duplicate photo across owners, rapid relist) into the moderation queue.
 */
class FraudAndRevocationTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->category = Category::create(['name' => 'Brakes', 'slug' => 'brakes-' . Str::random(4), 'sort_order' => 0]);
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

    private function verifiedDealer(): Vendor
    {
        $vendor = $this->vendor();
        foreach (['company_reg', 'banking'] as $d) {
            VendorVerification::create(['vendor_id' => $vendor->id, 'dimension' => $d, 'status' => 'approved', 'verified_at' => now()]);
        }
        app(TierEvaluator::class)->recompute($vendor);

        return $vendor->fresh();
    }

    private function product(Vendor $vendor, string $title, ?string $hash = null): Product
    {
        $p = Product::create(['vendor_id' => $vendor->id, 'category_id' => $this->category->id, 'title' => $title,
            'description' => 'x', 'price_zwl' => 100, 'price_usd' => 10, 'quantity' => 1, 'status' => 'active']);
        if ($hash) {
            $p->images()->create(['disk' => 'public', 'original_path' => Str::random(8) . '.jpg', 'display_order' => 0, 'image_hash' => $hash]);
        }

        return $p;
    }

    // ─── Revocation ───────────────────────────────────────────────────────────────

    public function test_revoking_badge_suppresses_tiers_until_reinstated(): void
    {
        $vendor = $this->verifiedDealer();
        $this->assertSame('verified_dealer', $vendor->verification_tier);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.vendors.badge.update', $vendor), ['action' => 'revoke', 'reason' => 'Fraud'])->assertRedirect();
        $this->assertTrue($vendor->fresh()->isBadgeRevoked());
        $this->assertNull($vendor->fresh()->verification_tier);
        $this->assertSame([], app(TierEvaluator::class)->earnedTiers($vendor->fresh()));
        $this->assertDatabaseHas('audit_logs', ['action' => 'badge.revoke', 'target_id' => $vendor->id]);

        $this->actingAs($admin)->post(route('admin.vendors.badge.update', $vendor), ['action' => 'reinstate'])->assertRedirect();
        $this->assertSame('verified_dealer', $vendor->fresh()->verification_tier);
    }

    public function test_admin_can_grant_manual_tier(): void
    {
        $vendor = $this->vendor();

        $this->actingAs($this->admin())
            ->post(route('admin.vendors.badge.update', $vendor), ['action' => 'grant', 'manual_tier' => 'manufacturer_authorized'])
            ->assertRedirect();

        $this->assertSame('manufacturer_authorized', $vendor->fresh()->verification_tier);
        $this->assertDatabaseHas('audit_logs', ['action' => 'badge.grant', 'target_id' => $vendor->id]);
    }

    public function test_non_admin_cannot_manage_badge(): void
    {
        $seller = User::factory()->create(['role' => 'private_seller', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $seller->assignRole('private_seller');

        $this->actingAs($seller)
            ->post(route('admin.vendors.badge.update', $this->vendor()), ['action' => 'revoke'])
            ->assertForbidden();
    }

    // ─── Fraud rules ────────────────────────────────────────────────────────────

    public function test_duplicate_photo_across_owners_is_flagged(): void
    {
        $a = $this->product($this->vendor(), 'Pads A', hash: 'SAMEHASH123');
        $b = $this->product($this->vendor(), 'Pads B', hash: 'SAMEHASH123'); // different vendor, same photo
        $clean = $this->product($this->vendor(), 'Pads C', hash: 'UNIQUEHASH');

        $created = app(FraudRuleService::class)->scan();

        $this->assertGreaterThanOrEqual(2, $created);
        $this->assertSame(1, ListingReport::where('reportable_id', $a->id)->where('reason', 'duplicate')->count());
        $this->assertSame(1, ListingReport::where('reportable_id', $b->id)->where('reason', 'duplicate')->count());
        $this->assertSame(0, ListingReport::where('reportable_id', $clean->id)->count());
    }

    public function test_same_owner_reusing_own_photo_is_not_flagged(): void
    {
        $vendor = $this->vendor();
        $this->product($vendor, 'Pads A', hash: 'OWNHASH');
        $this->product($vendor, 'Pads B', hash: 'OWNHASH'); // same owner → fine

        app(FraudRuleService::class)->scan();

        $this->assertSame(0, ListingReport::where('reason', 'duplicate')->count());
    }

    public function test_rapid_relist_is_flagged(): void
    {
        config(['verification.fraud.rapid_relist_threshold' => 3]);
        $vendor = $this->vendor();
        for ($i = 0; $i < 3; $i++) {
            $this->product($vendor, 'Toyota Hilux Bumper'); // same title, same owner, now
        }

        $created = app(FraudRuleService::class)->scan();

        $this->assertGreaterThanOrEqual(3, $created);
        $this->assertSame(3, ListingReport::where('reason', 'duplicate')->where('source', 'auto')->count());
    }

    public function test_fraud_scan_is_idempotent(): void
    {
        $this->product($this->vendor(), 'X', hash: 'DUP');
        $this->product($this->vendor(), 'Y', hash: 'DUP');

        app(FraudRuleService::class)->scan();
        app(FraudRuleService::class)->scan();

        $this->assertSame(2, ListingReport::where('reason', 'duplicate')->count());
    }
}
