<?php

namespace Tests\Feature\Cart;

use App\Models\Vendor;
use App\Modules\Cart\Services\CartService;
use App\Modules\Categories\Models\Category;
use App\Modules\Products\Models\Product;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CartTest extends TestCase
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
            'name'          => 'Vendor ' . Str::random(4),
            'slug'          => 'vendor-' . Str::random(6),
            'contact_email' => 'v@x.com',
            'status'        => 'approved',
        ], $attrs));
    }

    private function category(): Category
    {
        return Category::create(['name' => 'Parts', 'slug' => 'parts-' . Str::random(6), 'sort_order' => 0]);
    }

    private function product(Vendor $vendor, Category $category, array $attrs = []): Product
    {
        return Product::create(array_merge([
            'vendor_id'   => $vendor->id,
            'category_id' => $category->id,
            'title'       => 'Item ' . Str::random(4),
            'description' => 'A product.',
            'price_zwl'   => 100,
            'quantity'    => 10,
            'status'      => 'active',
        ], $attrs));
    }

    private function cart(): CartService
    {
        return app(CartService::class);
    }

    private function settings(): SettingsService
    {
        return app(SettingsService::class);
    }

    // ─── Basic mutations ────────────────────────────────────────────────────────

    public function test_add_update_remove_and_count(): void
    {
        $vendor   = $this->vendor();
        $category = $this->category();
        $p1 = $this->product($vendor, $category);
        $p2 = $this->product($vendor, $category);

        $cart = $this->cart();
        $cart->add($p1->id, 2);
        $cart->add($p2->id);
        $this->assertSame(3, $cart->count());

        $cart->update($p1->id, 5);
        $this->assertSame(6, $cart->count());

        $cart->update($p2->id, 0); // zero removes
        $this->assertSame(5, $cart->count());

        $cart->remove($p1->id);
        $this->assertTrue($cart->isEmpty());
    }

    // ─── Vendor + track splitting ───────────────────────────────────────────────

    public function test_mixed_vendor_cart_splits_into_per_vendor_groups(): void
    {
        $category = $this->category();
        $vendorA  = $this->vendor();
        $vendorB  = $this->vendor();

        $cart = $this->cart();
        $cart->add($this->product($vendorA, $category)->id);
        $cart->add($this->product($vendorB, $category)->id);

        $groups = $cart->groups();

        $this->assertCount(2, $groups);
        $this->assertEqualsCanonicalizing(
            [$vendorA->id, $vendorB->id],
            array_map(fn ($g) => $g->vendorId, $groups)
        );
    }

    public function test_same_vendor_different_tracks_split_into_separate_groups(): void
    {
        $category = $this->category();
        $vendor   = $this->vendor();

        $cart = $this->cart();
        $cart->add($this->product($vendor, $category, ['fulfilment_type' => 'fbs'])->id);
        $cart->add($this->product($vendor, $category, ['fulfilment_type' => 'vendor'])->id);

        $groups = $cart->groups();

        $this->assertCount(2, $groups);
        $this->assertEqualsCanonicalizing(['fbs', 'vendor'], array_map(fn ($g) => $g->track, $groups));
    }

    // ─── Delivery estimate ──────────────────────────────────────────────────────

    public function test_fbs_group_uses_settings_delivery_fee_and_vendor_group_has_none(): void
    {
        $this->settings()->set('delivery.fbs_default_fee', 7.50);
        $category = $this->category();
        $vendor   = $this->vendor();

        $cart = $this->cart();
        $cart->add($this->product($vendor, $category, ['fulfilment_type' => 'fbs'])->id);
        $cart->add($this->product($vendor, $category, ['fulfilment_type' => 'vendor'])->id);

        $byTrack = collect($cart->groups())->keyBy('track');

        $this->assertSame(7.5, $byTrack['fbs']->deliveryFee);
        $this->assertNull($byTrack['vendor']->deliveryFee);
    }

    // ─── COD matrix (BUSINESS_MODEL.md §3) ──────────────────────────────────────

    public function test_fbs_cod_available_when_enabled_and_product_allows(): void
    {
        $this->settings()->set('cod.fbs_enabled', true);
        $category = $this->category();
        $vendor   = $this->vendor(['cod_eligible' => false]); // FBS COD does NOT need vendor eligibility

        $cart = $this->cart();
        $cart->add($this->product($vendor, $category, ['fulfilment_type' => 'fbs', 'cod_allowed' => true])->id);

        $this->assertTrue($cart->groups()[0]->codAvailable);
    }

    public function test_fbs_cod_blocked_when_setting_disabled(): void
    {
        $this->settings()->set('cod.fbs_enabled', false);
        $category = $this->category();
        $vendor   = $this->vendor();

        $cart = $this->cart();
        $cart->add($this->product($vendor, $category, ['fulfilment_type' => 'fbs', 'cod_allowed' => true])->id);

        $this->assertFalse($cart->groups()[0]->codAvailable);
    }

    public function test_vf_cod_requires_vendor_eligibility(): void
    {
        $this->settings()->set('cod.vf_enabled', true);
        $category = $this->category();

        // Eligible vendor → COD available
        $eligible = $this->vendor(['cod_eligible' => true]);
        $cart = $this->cart();
        $cart->add($this->product($eligible, $category, ['fulfilment_type' => 'vendor', 'cod_allowed' => true])->id);
        $this->assertTrue($cart->groups()[0]->codAvailable);

        // Ineligible vendor → COD blocked
        $cart->clear();
        $ineligible = $this->vendor(['cod_eligible' => false]);
        $cart->add($this->product($ineligible, $category, ['fulfilment_type' => 'vendor', 'cod_allowed' => true])->id);
        $this->assertFalse($cart->groups()[0]->codAvailable);
    }

    public function test_cod_blocked_when_product_disallows(): void
    {
        $this->settings()->set('cod.fbs_enabled', true);
        $category = $this->category();
        $vendor   = $this->vendor();

        $cart = $this->cart();
        $cart->add($this->product($vendor, $category, ['fulfilment_type' => 'fbs', 'cod_allowed' => false])->id);

        $this->assertFalse($cart->groups()[0]->codAvailable);
    }

    // ─── HTTP endpoints ─────────────────────────────────────────────────────────

    public function test_add_to_cart_endpoint_adds_item(): void
    {
        $product = $this->product($this->vendor(), $this->category());

        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])
            ->assertRedirect();

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee($product->title);
    }

    public function test_cannot_add_inactive_product(): void
    {
        $product = $this->product($this->vendor(), $this->category(), ['status' => 'pending']);

        $this->post(route('cart.add'), ['product_id' => $product->id])
            ->assertRedirect();

        $this->assertSame(0, $this->cart()->count());
    }
}
