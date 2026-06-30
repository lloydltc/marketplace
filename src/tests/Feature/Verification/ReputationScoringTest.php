<?php

namespace Tests\Feature\Verification;

use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Products\Models\Product;
use App\Modules\Verification\Models\VendorVerification;
use App\Modules\Verification\Services\ReputationService;
use App\Modules\Verification\Services\TierEvaluator;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * VB3: config-weighted reputation scoring that degrades gracefully when signals
 * are missing, persists a snapshot, caches the score, and feeds the Top-Rated tier.
 */
class ReputationScoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function vendor(): Vendor
    {
        return Vendor::create(['name' => 'V ' . Str::random(4), 'slug' => 'v-' . Str::random(6), 'contact_email' => Str::random(5) . '@x.com', 'status' => 'approved']);
    }

    public function test_new_vendor_with_no_signals_scores_zero_without_error(): void
    {
        $vendor = $this->vendor();

        $score = app(ReputationService::class)->recompute($vendor);

        $this->assertSame(0, $score);
        $this->assertDatabaseHas('vendor_reputation', ['vendor_id' => $vendor->id, 'score' => 0]);
    }

    public function test_disputes_component_reflects_cancel_rate(): void
    {
        $vendor = $this->vendor();
        // 3 orders, 1 cancelled → dispute-free rate ~67.
        foreach (['completed', 'completed', 'cancelled'] as $status) {
            DB::table('orders')->insert([
                'id' => (string) Str::uuid(), 'order_number' => 'ORD-' . Str::upper(Str::random(8)),
                'vendor_id' => $vendor->id, 'buyer_name' => 'B', 'buyer_email' => 'b@x.com',
                'buyer_phone' => 'x', 'buyer_address' => '1 St', 'buyer_city' => 'Harare',
                'fulfilment_track' => 'vendor', 'payment_method' => 'prepaid', 'status' => $status,
                'currency' => 'ZWL', 'subtotal' => 100, 'delivery_fee' => 0, 'total' => 100,
                'commission_rate_applied' => 10, 'commission_amount' => 10, 'net_to_vendor' => 90,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $components = app(ReputationService::class)->components($vendor);

        $this->assertSame(67, $components['disputes']);
        $this->assertNull($components['rating']);   // no rated listings
        $this->assertNull($components['response']); // no leads
    }

    public function test_quality_component_from_listing_completeness(): void
    {
        $vendor = $this->vendor();
        $cat = Category::create(['name' => 'Brakes', 'slug' => 'brakes-' . Str::random(4), 'sort_order' => 0]);
        // 1 complete (desc + image), 1 incomplete → 50.
        $complete = Product::create(['vendor_id' => $vendor->id, 'category_id' => $cat->id, 'title' => 'A', 'description' => 'full', 'price_zwl' => 100, 'price_usd' => 10, 'quantity' => 1, 'status' => 'active']);
        $complete->images()->create(['disk' => 'public', 'original_path' => 'x.jpg', 'display_order' => 0]);
        Product::create(['vendor_id' => $vendor->id, 'category_id' => $cat->id, 'title' => 'B', 'description' => '', 'price_zwl' => 100, 'price_usd' => 10, 'quantity' => 1, 'status' => 'active']);

        $this->assertSame(50, app(ReputationService::class)->components($vendor)['quality']);
    }

    public function test_high_reputation_unlocks_top_rated_tier(): void
    {
        $vendor = $this->vendor();
        // Earn the base dimensions for top_rated.
        foreach (['company_reg', 'banking'] as $d) {
            VendorVerification::create(['vendor_id' => $vendor->id, 'dimension' => $d, 'status' => 'approved', 'verified_at' => now()]);
        }
        // Force a high score via the cache, then evaluate.
        $vendor->forceFill(['reputation_score' => 90])->save();

        $this->assertContains('top_rated', app(TierEvaluator::class)->earnedTiers($vendor->fresh()));
    }

    public function test_recompute_command_runs(): void
    {
        $this->vendor();
        $this->artisan('reputation:recompute')->assertExitCode(0);
    }
}
