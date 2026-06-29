<?php

namespace Tests\Feature\Verification;

use App\Models\Vendor;
use App\Modules\Verification\Models\VendorVerification;
use App\Modules\Verification\Services\TierEvaluator;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * VB1: config-driven trust-badge tier evaluation from approved verification
 * dimensions + reputation + manual grants.
 */
class TierEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function vendor(array $attrs = []): Vendor
    {
        return Vendor::create(array_merge([
            'name' => 'V ' . Str::random(4), 'slug' => 'v-' . Str::random(6),
            'contact_email' => Str::random(5) . '@x.com', 'status' => 'approved',
        ], $attrs));
    }

    private function approve(Vendor $vendor, string $dimension, ?\DateTimeInterface $expires = null): void
    {
        VendorVerification::create([
            'vendor_id' => $vendor->id, 'dimension' => $dimension, 'status' => 'approved',
            'verified_at' => now(), 'expires_at' => $expires,
        ]);
    }

    public function test_verified_dealer_needs_company_reg_and_banking(): void
    {
        $vendor = $this->vendor();
        $evaluator = app(TierEvaluator::class);

        $this->approve($vendor, 'company_reg');
        $this->assertNotContains('verified_dealer', $evaluator->earnedTiers($vendor->fresh()));

        $this->approve($vendor, 'banking');
        $this->assertContains('verified_dealer', $evaluator->earnedTiers($vendor->fresh()));
    }

    public function test_premium_requires_all_four_dimensions(): void
    {
        $vendor = $this->vendor();
        foreach (['company_reg', 'tax', 'banking'] as $d) {
            $this->approve($vendor, $d);
        }
        $this->assertNotContains('premium_dealer', app(TierEvaluator::class)->earnedTiers($vendor->fresh()));

        $this->approve($vendor, 'location');
        $this->assertContains('premium_dealer', app(TierEvaluator::class)->earnedTiers($vendor->fresh()));
    }

    public function test_expired_dimension_does_not_count(): void
    {
        $vendor = $this->vendor();
        $this->approve($vendor, 'company_reg', expires: now()->subDay());
        $this->approve($vendor, 'banking');

        $this->assertNotContains('verified_dealer', app(TierEvaluator::class)->earnedTiers($vendor->fresh()));
    }

    public function test_manufacturer_authorized_is_manual_only(): void
    {
        $vendor = $this->vendor();
        $this->approve($vendor, 'company_reg');
        $this->approve($vendor, 'banking');

        $this->assertNotContains('manufacturer_authorized', app(TierEvaluator::class)->earnedTiers($vendor->fresh()));

        $vendor->update(['manual_tier' => 'manufacturer_authorized']);
        $this->assertContains('manufacturer_authorized', app(TierEvaluator::class)->earnedTiers($vendor->fresh()));
    }

    public function test_top_rated_requires_reputation_threshold(): void
    {
        $vendor = $this->vendor();
        $this->approve($vendor, 'company_reg');
        $this->approve($vendor, 'banking');

        $this->assertNotContains('top_rated', app(TierEvaluator::class)->earnedTiers($vendor->fresh()));

        $vendor->forceFill(['reputation_score' => 85])->save();
        $this->assertContains('top_rated', app(TierEvaluator::class)->earnedTiers($vendor->fresh()));
    }

    public function test_primary_tier_is_highest_ranked_and_persists(): void
    {
        $vendor = $this->vendor();
        foreach (['company_reg', 'tax', 'banking', 'location'] as $d) {
            $this->approve($vendor, $d);
        }
        $vendor->update(['manual_tier' => 'manufacturer_authorized']); // rank 5 (highest)

        $tier = app(TierEvaluator::class)->recompute($vendor->fresh());

        $this->assertSame('manufacturer_authorized', $tier);
        $this->assertSame('manufacturer_authorized', $vendor->fresh()->verification_tier);
        $this->assertSame('Manufacturer-Authorized', $vendor->fresh()->badgeTierLabel());
    }
}
