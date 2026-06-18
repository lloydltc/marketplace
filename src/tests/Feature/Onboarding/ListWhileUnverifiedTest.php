<?php

namespace Tests\Feature\Onboarding;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Products\Models\Product;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * R4 / F12–F13: a pending (unverified) seller can build and publish listings,
 * but those listings are display-only — not transactable — until approval,
 * unless the platform flag `sellers.unverified_can_transact` is switched on.
 */
class ListWhileUnverifiedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function vendor(string $status): Vendor
    {
        return Vendor::create([
            'name'          => 'Vendor ' . Str::random(4),
            'slug'          => 'vendor-' . Str::random(6),
            'contact_email' => 'v@x.com',
            'status'        => $status,
        ]);
    }

    private function category(): Category
    {
        return Category::create(['name' => 'Parts', 'slug' => 'parts-' . Str::random(6), 'sort_order' => 0]);
    }

    private function product(Vendor $vendor, Category $category): Product
    {
        return Product::create([
            'vendor_id'   => $vendor->id,
            'category_id' => $category->id,
            'title'       => 'Item ' . Str::random(4),
            'description' => 'A product.',
            'price_zwl'   => 100,
            'quantity'    => 10,
            'status'      => 'active',
        ]);
    }

    // ─── Model-level gate ────────────────────────────────────────────────────────

    public function test_pending_vendor_cannot_transact_by_default(): void
    {
        $this->assertFalse($this->vendor('pending')->canTransact());
    }

    public function test_approved_vendor_can_always_transact(): void
    {
        $this->assertTrue($this->vendor('approved')->canTransact());
    }

    public function test_kill_switch_lets_pending_vendor_transact(): void
    {
        app(SettingsService::class)->set('sellers.unverified_can_transact', true);

        $this->assertTrue($this->vendor('pending')->canTransact());
    }

    // ─── Cart gate (HTTP) ─────────────────────────────────────────────────────────

    public function test_cannot_add_unverified_vendor_product_to_cart(): void
    {
        $product = $this->product($this->vendor('pending'), $this->category());

        $this->post(route('cart.add'), ['product_id' => $product->id])
            ->assertSessionHasErrors('cart');

        $this->assertSame(0, app(\App\Modules\Cart\Services\CartService::class)->count());
    }

    public function test_can_add_approved_vendor_product_to_cart(): void
    {
        $product = $this->product($this->vendor('approved'), $this->category());

        $this->post(route('cart.add'), ['product_id' => $product->id])
            ->assertRedirect();

        $this->assertSame(1, app(\App\Modules\Cart\Services\CartService::class)->count());
    }

    public function test_approval_lifts_the_cart_gate(): void
    {
        $vendor  = $this->vendor('pending');
        $product = $this->product($vendor, $this->category());

        // Blocked while pending …
        $this->post(route('cart.add'), ['product_id' => $product->id])
            ->assertSessionHasErrors('cart');

        // … then approved → allowed.
        $vendor->update(['status' => 'approved']);

        $this->post(route('cart.add'), ['product_id' => $product->id])
            ->assertRedirect();
        $this->assertSame(1, app(\App\Modules\Cart\Services\CartService::class)->count());
    }

    // ─── Public listing badge ──────────────────────────────────────────────────────

    public function test_unverified_badge_shows_on_product_page(): void
    {
        $product = $this->product($this->vendor('pending'), $this->category());

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Unverified seller');
    }

    public function test_no_badge_for_approved_vendor_product(): void
    {
        $product = $this->product($this->vendor('approved'), $this->category());

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertDontSee('Unverified seller');
    }
}
