<?php

namespace Tests\Feature\Promotions;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Modules\Promotions\Models\PromotionPurchase;
use App\Modules\Promotions\Models\VendorPackageSubscription;
use App\Modules\Promotions\Services\PromotionService;
use App\Modules\Settings\Services\SettingsService;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use App\Modules\Vehicles\Repositories\VehicleRepositoryInterface;
use App\Modules\Payments\Services\PesepayClient;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function vendorWithAdmin(): array
    {
        $vendor = Vendor::create([
            'name' => 'Dealer', 'slug' => 'dealer-' . Str::random(5), 'contact_email' => 'd@x.com', 'status' => 'approved',
        ]);
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'vendor_admin', 'email_verified_at' => now()]);
        $admin->assignRole('vendor_admin');
        $admin->vendors()->attach($vendor->id, ['vendor_role' => 'admin', 'invited_at' => now(), 'joined_at' => now()]);
        return [$vendor, $admin];
    }

    private function vehicle(Vendor $vendor, array $attrs = []): Vehicle
    {
        $suffix = Str::random(5);
        $make  = VehicleMake::create(['name' => 'Make ' . $suffix, 'slug' => 'make-' . $suffix, 'sort_order' => 0]);
        $model = VehicleModel::create(['make_id' => $make->id, 'name' => 'Model ' . $suffix, 'slug' => 'model-' . $suffix]);

        return Vehicle::create(array_merge([
            'vendor_id' => $vendor->id, 'make_id' => $make->id, 'model_id' => $model->id,
            'year' => 2020, 'body_type' => 'pickup', 'transmission' => 'manual', 'fuel_type' => 'diesel',
            'mileage' => 1000, 'color' => 'white', 'condition' => 'used', 'status' => 'active', 'price_zwl' => 100,
        ], $attrs));
    }

    private function fakeGateway(): void
    {
        Http::fake(['*v1/payments/initiate' => Http::response([
            'payload' => app(PesepayClient::class)->encrypt(['referenceNumber' => 'PRM1', 'redirectUrl' => 'https://pay.test/prm1']),
        ], 200)]);
    }

    // ─── Credit-funded promotion ────────────────────────────────────────────────

    public function test_feature_via_package_credit_applies_for_free(): void
    {
        [$vendor]  = $this->vendorWithAdmin();
        $vehicle   = $this->vehicle($vendor);
        VendorPackageSubscription::create([
            'vendor_id' => $vendor->id, 'feature_credits_remaining' => 2, 'bump_credits_remaining' => 2,
            'status' => 'active', 'expires_at' => now()->addDays(30),
        ]);

        $result = app(PromotionService::class)->feature($vehicle);

        $this->assertSame('credit', $result['funded']);
        $this->assertTrue($vehicle->fresh()->isFeatured());
        $this->assertSame(1, app(PromotionService::class)->activeSubscription($vendor->id)->feature_credits_remaining);
        $this->assertDatabaseHas('promotion_purchases', ['vehicle_id' => $vehicle->id, 'type' => 'featured', 'funded_by' => 'credit', 'status' => 'completed']);
    }

    // ─── Gateway-funded promotion + webhook apply ───────────────────────────────

    public function test_feature_via_gateway_applies_on_webhook_and_records_revenue(): void
    {
        $this->fakeGateway();
        [$vendor]  = $this->vendorWithAdmin();
        $vehicle   = $this->vehicle($vendor);

        $result = app(PromotionService::class)->feature($vehicle);
        $this->assertSame('gateway', $result['funded']);
        $this->assertFalse($vehicle->fresh()->isFeatured()); // not yet — payment pending

        $this->postJson(route('payments.webhook'), [
            'payload' => app(PesepayClient::class)->encrypt(['referenceNumber' => 'PRM1', 'transactionStatus' => 'SUCCESS']),
        ])->assertOk();

        $this->assertTrue($vehicle->fresh()->isFeatured());
        $this->assertSame(10.0, (float) PromotionPurchase::where('status', 'completed')->where('funded_by', 'gateway')->sum('amount'));
    }

    // ─── Badge gated on documents ───────────────────────────────────────────────

    public function test_badge_requires_approved_documents(): void
    {
        [$vendor, $admin] = $this->vendorWithAdmin();
        $vehicle          = $this->vehicle($vendor);

        $this->actingAs($admin)->post(route('vendor.vehicles.promote', $vehicle), ['action' => 'badge'])
            ->assertSessionHasErrors('promotion');
        $this->assertDatabaseMissing('promotion_purchases', ['vehicle_id' => $vehicle->id, 'type' => 'badge']);
    }

    public function test_badge_allowed_with_approved_documents(): void
    {
        $this->fakeGateway();
        [$vendor, $admin] = $this->vendorWithAdmin();
        $vehicle          = $this->vehicle($vendor);
        VendorDocument::create([
            'vendor_id' => $vendor->id, 'document_type' => 'business_registration',
            'file_path' => 'docs/x.pdf', 'status' => 'approved',
        ]);

        $this->actingAs($admin)->post(route('vendor.vehicles.promote', $vehicle), ['action' => 'badge'])
            ->assertRedirect('https://pay.test/prm1');
        $this->assertDatabaseHas('promotion_purchases', ['vehicle_id' => $vehicle->id, 'type' => 'badge']);
    }

    // ─── Auto-demotion (no job) ─────────────────────────────────────────────────

    public function test_expired_featured_listing_is_not_boosted(): void
    {
        [$vendor] = $this->vendorWithAdmin();
        app(SettingsService::class)->set('search.featured_vehicle_boost', 100);

        // Expired-featured vehicle created EARLIER; a plain vehicle created LATER.
        $expired = $this->vehicle($vendor, ['featured_until' => now()->subDay()]);
        Vehicle::where('id', $expired->id)->update(['created_at' => now()->subDay()]);
        $plain = $this->vehicle($vendor);
        Vehicle::where('id', $plain->id)->update(['created_at' => now()]);

        $results = app(VehicleRepositoryInterface::class)->paginatePublic([])->getCollection();

        // The expired feature earns no boost → the newer plain listing ranks first.
        $this->assertSame($plain->id, $results->first()->id);
        $this->assertFalse($expired->fresh()->isFeatured());
    }

    // ─── Buyers never check out a vehicle ───────────────────────────────────────

    public function test_buyer_cannot_add_a_vehicle_to_the_cart(): void
    {
        [$vendor] = $this->vendorWithAdmin();
        $vehicle  = $this->vehicle($vendor);

        // The cart only accepts products; a vehicle id resolves to no product.
        $this->post(route('cart.add'), ['product_id' => $vehicle->id])->assertRedirect();
        $this->get(route('cart.index'))->assertOk()->assertDontSee($vehicle->displayTitle());
    }
}
