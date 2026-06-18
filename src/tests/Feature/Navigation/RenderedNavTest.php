<?php

namespace Tests\Feature\Navigation;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Products\Models\Product;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * P1: asserts the *rendered navigation/view*, not just route permissions — this
 * closes the "passes in test but wrong in UI" gap the P0 verification found.
 * Corrected model: only `customer` (+ guests) get buyer surfaces; sellers get a
 * "Sales" surface; admins get neither.
 */
class RenderedNavTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create([
            'role' => $role, 'status' => 'active',
            'email_verified_at' => now(), 'force_password_change' => false,
        ]);
        $u->assignRole($role);

        return $u;
    }

    private function vendorUser(string $role): User
    {
        $vendor = Vendor::create([
            'name' => 'V ' . Str::random(4), 'slug' => 'v-' . Str::random(6),
            'contact_email' => Str::random(5) . '@x.com', 'status' => 'approved',
        ]);
        $u = $this->user($role);
        $vendor->users()->attach($u->id, ['vendor_role' => $role === 'vendor_admin' ? 'admin' : 'worker', 'joined_at' => now()]);

        return $u;
    }

    // ─── Buyer nav: customer + guest only ──────────────────────────────────────────

    public function test_customer_sees_buyer_nav(): void
    {
        $this->actingAs($this->user('customer'))->get(route('home'))
            ->assertOk()
            ->assertSee('My orders')
            ->assertSee('Saved searches')
            ->assertDontSee('>Sales<', false);
    }

    public function test_guest_sees_browse_and_cart_not_buyer_account_links(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('>Shop<', false)
            ->assertSee('href="' . route('cart.index') . '"', false)
            ->assertDontSee('My orders'); // account links require auth
    }

    public function test_private_seller_has_sales_not_buyer_nav(): void
    {
        $this->actingAs($this->user('private_seller'))->get(route('home'))
            ->assertOk()
            ->assertSee('>Sales<', false)
            ->assertSee('>Dashboard<', false)
            ->assertDontSee('My orders')
            ->assertDontSee('Saved searches')
            ->assertDontSee('href="' . route('cart.index') . '"', false);
    }

    public function test_vendor_admin_has_sales_not_buyer_nav(): void
    {
        $this->actingAs($this->vendorUser('vendor_admin'))->get(route('home'))
            ->assertOk()
            ->assertSee('>Sales<', false)
            ->assertDontSee('My orders')
            ->assertDontSee('href="' . route('cart.index') . '"', false);
    }

    public function test_admin_has_neither_buyer_nor_sales_nav(): void
    {
        $this->actingAs($this->user('admin'))->get(route('home'))
            ->assertOk()
            ->assertSee('>Dashboard<', false)
            ->assertDontSee('My orders')
            ->assertDontSee('Saved searches')
            ->assertDontSee('href="' . route('cart.index') . '"', false)
            ->assertDontSee('>Sales<', false);
    }

    // ─── Catalogue buy CTA: shoppers only ─────────────────────────────────────────

    private function product(): Product
    {
        $vendor = Vendor::create([
            'name' => 'PV', 'slug' => 'pv-' . Str::random(5),
            'contact_email' => 'pv@x.com', 'status' => 'approved',
        ]);
        $cat = Category::create(['name' => 'P', 'slug' => 'p-' . Str::random(5), 'sort_order' => 0]);

        return Product::create([
            'vendor_id' => $vendor->id, 'category_id' => $cat->id,
            'title' => 'Brake Pad Set', 'description' => 'x',
            'price_zwl' => 100, 'quantity' => 5, 'status' => 'active',
        ]);
    }

    public function test_customer_sees_add_to_cart_on_product(): void
    {
        $this->actingAs($this->user('customer'))->get(route('products.show', $this->product()))
            ->assertOk()
            ->assertSee('Add to cart');
    }

    public function test_seller_does_not_see_add_to_cart_on_product(): void
    {
        $this->actingAs($this->user('private_seller'))->get(route('products.show', $this->product()))
            ->assertOk()
            ->assertDontSee('Add to cart')
            ->assertSee('Purchasing is available to customer accounts');
    }

    public function test_admin_does_not_see_add_to_cart_on_product(): void
    {
        $this->actingAs($this->user('admin'))->get(route('products.show', $this->product()))
            ->assertOk()
            ->assertDontSee('Add to cart');
    }

    // ─── Seller Sales surface renders ─────────────────────────────────────────────

    public function test_private_seller_sales_page_renders(): void
    {
        $this->actingAs($this->user('private_seller'))->get(route('seller.sales.index'))
            ->assertOk()
            ->assertSee('Sales');
    }

    public function test_non_seller_cannot_reach_seller_sales(): void
    {
        $this->actingAs($this->user('customer'))->get(route('seller.sales.index'))->assertForbidden();
    }
}
