<?php

namespace Tests\Feature\Parts;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Parts\Models\Part;
use App\Modules\Parts\Models\PartBundle;
use App\Modules\Products\Models\Product;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PM6: service-kit bundles — pricing, stock, add-to-cart expansion, vendor build.
 */
class ServiceKitBundleTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->vendor = Vendor::create(['name' => 'Parts Co', 'slug' => 'pc-' . Str::random(5), 'contact_email' => 'p@x.com', 'status' => 'approved']);
        $this->category = Category::create(['name' => 'Service', 'slug' => 'service-' . Str::random(4), 'sort_order' => 0]);
    }

    private function offering(string $title, float $price, int $qty = 10): Product
    {
        $part = Part::create(['name' => $title, 'category_id' => $this->category->id]);

        return Product::create([
            'vendor_id' => $this->vendor->id, 'part_id' => $part->id, 'category_id' => $this->category->id,
            'title' => $title, 'description' => 'x', 'price_zwl' => $price * 10, 'price_usd' => $price,
            'quantity' => $qty, 'status' => 'active',
        ]);
    }

    private function kit(array $components, ?float $setPrice = null): PartBundle
    {
        $bundle = PartBundle::create(['vendor_id' => $this->vendor->id, 'name' => 'Minor Service Kit', 'price_usd' => $setPrice]);
        foreach ($components as [$product, $qty]) {
            $bundle->items()->create(['product_id' => $product->id, 'qty' => $qty]);
        }

        return $bundle->load('items.product');
    }

    public function test_price_sums_components_or_uses_set_price(): void
    {
        $oil = $this->offering('Oil', 20);
        $filter = $this->offering('Filter', 8);

        $summed = $this->kit([[$oil, 1], [$filter, 2]]);
        $this->assertSame(36.0, $summed->effectivePrice()); // 20 + 8*2

        $fixed = $this->kit([[$oil, 1], [$filter, 1]], setPrice: 25.0);
        $this->assertSame(25.0, $fixed->effectivePrice());
    }

    public function test_in_stock_requires_every_component(): void
    {
        $a = $this->offering('A', 10, qty: 5);
        $b = $this->offering('B', 10, qty: 1);

        $this->assertTrue($this->kit([[$a, 2], [$b, 1]])->isInStock());
        $this->assertFalse($this->kit([[$a, 2], [$b, 5]])->isInStock()); // b short
    }

    public function test_add_kit_expands_into_component_cart_lines(): void
    {
        $oil = $this->offering('Oil', 20);
        $filter = $this->offering('Filter', 8);
        $kit = $this->kit([[$oil, 1], [$filter, 2]]);

        $this->post(route('bundles.add', $kit))->assertRedirect(route('cart.index'));

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Oil')
            ->assertSee('Filter');
    }

    public function test_out_of_stock_kit_cannot_be_added(): void
    {
        $a = $this->offering('A', 10, qty: 0);
        $kit = $this->kit([[$a, 1]]);

        $this->post(route('bundles.add', $kit))->assertSessionHasErrors('cart');
    }

    public function test_public_kit_page_renders(): void
    {
        $oil = $this->offering('Oil', 20);
        $kit = $this->kit([[$oil, 1]]);

        $this->get(route('bundles.show', $kit->slug))
            ->assertOk()
            ->assertSee('Minor Service Kit')
            ->assertSee('Oil');
    }

    public function test_vendor_can_build_a_kit_from_own_offerings(): void
    {
        $admin = User::factory()->create(['role' => 'vendor_admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $admin->assignRole('vendor_admin');
        $this->vendor->users()->attach($admin->id, ['vendor_role' => 'admin', 'joined_at' => now()]);
        $oil = $this->offering('Oil', 20);
        $filter = $this->offering('Filter', 8);

        $this->actingAs($admin)->post(route('vendor.bundles.store'), [
            'name'  => 'My Kit',
            'items' => [$oil->id => 1, $filter->id => 2],
        ])->assertRedirect(route('vendor.bundles.index'));

        $bundle = PartBundle::where('vendor_id', $this->vendor->id)->first();
        $this->assertNotNull($bundle);
        $this->assertSame(2, $bundle->items()->count());
    }

    public function test_vendor_cannot_bundle_another_vendors_offering(): void
    {
        $admin = User::factory()->create(['role' => 'vendor_admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $admin->assignRole('vendor_admin');
        $this->vendor->users()->attach($admin->id, ['vendor_role' => 'admin', 'joined_at' => now()]);

        $otherVendor = Vendor::create(['name' => 'Other', 'slug' => 'o-' . Str::random(5), 'contact_email' => 'o@x.com', 'status' => 'approved']);
        $foreign = Product::create([
            'vendor_id' => $otherVendor->id, 'category_id' => $this->category->id, 'title' => 'Foreign',
            'description' => 'x', 'price_zwl' => 100, 'price_usd' => 10, 'quantity' => 5, 'status' => 'active',
        ]);

        $this->actingAs($admin)->post(route('vendor.bundles.store'), [
            'name'  => 'Sneaky Kit',
            'items' => [$foreign->id => 1],
        ])->assertSessionHasErrors('items');

        $this->assertSame(0, PartBundle::where('vendor_id', $this->vendor->id)->count());
    }
}
