<?php

namespace Tests\Feature\Parts;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Parts\Models\Part;
use App\Modules\Products\Exceptions\InsufficientStockException;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Services\InventoryService;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PM2: products-as-offerings linked to canonical parts + auditable inventory.
 */
class OfferingInventoryTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->vendor = Vendor::create(['name' => 'Parts Co', 'slug' => 'parts-' . Str::random(5), 'contact_email' => 'p@x.com', 'status' => 'approved']);
        $this->category = Category::create(['name' => 'Brakes', 'slug' => 'brakes-' . Str::random(4), 'sort_order' => 0]);
    }

    private function offering(int $qty = 10, ?Part $part = null): Product
    {
        return Product::create([
            'vendor_id' => $this->vendor->id, 'part_id' => $part?->id, 'category_id' => $this->category->id,
            'title' => 'Brake Pads', 'description' => 'x', 'price_zwl' => 100, 'price_usd' => 10,
            'quantity' => $qty, 'condition' => 'new', 'low_stock_threshold' => 3, 'status' => 'active',
        ]);
    }

    public function test_multiple_vendors_offer_the_same_canonical_part(): void
    {
        $part = Part::create(['name' => 'Front Brake Pads']);
        $v2 = Vendor::create(['name' => 'Other', 'slug' => 'other-' . Str::random(5), 'contact_email' => 'o@x.com', 'status' => 'approved']);

        $a = $this->offering(5, $part);
        $b = Product::create(['vendor_id' => $v2->id, 'part_id' => $part->id, 'category_id' => $this->category->id,
            'title' => 'Front Pads', 'description' => 'x', 'price_zwl' => 90, 'price_usd' => 9, 'quantity' => 2, 'status' => 'active']);

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $part->offerings()->pluck('id')->all());
        $this->assertSame($part->id, $a->part->id);
    }

    public function test_restock_and_sale_are_audited_with_balance(): void
    {
        $offering = $this->offering(10);
        $svc = app(InventoryService::class);

        $this->assertSame(13, $svc->restock($offering, 3, 'PO-1'));
        $this->assertSame(11, $svc->recordSale($offering, 2, 'order-1'));

        $this->assertSame(11, (int) $offering->fresh()->quantity);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $offering->id, 'type' => 'restock', 'qty' => 3, 'balance_after' => 13]);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $offering->id, 'type' => 'sale', 'qty' => -2, 'balance_after' => 11]);
    }

    public function test_reserve_then_release_round_trips(): void
    {
        $offering = $this->offering(5);
        $svc = app(InventoryService::class);

        $this->assertSame(3, $svc->reserve($offering, 2, 'order-9'));
        $this->assertSame(5, $svc->release($offering, 2, 'order-9'));
    }

    public function test_stock_never_goes_negative(): void
    {
        $offering = $this->offering(1);
        $svc = app(InventoryService::class);

        $this->expectException(InsufficientStockException::class);
        $svc->recordSale($offering, 5);
    }

    public function test_failed_sale_leaves_stock_and_trail_untouched(): void
    {
        $offering = $this->offering(1);
        $svc = app(InventoryService::class);

        try {
            $svc->reserve($offering, 5);
        } catch (InsufficientStockException) {
            // expected
        }

        $this->assertSame(1, (int) $offering->fresh()->quantity);
        $this->assertSame(0, $offering->inventoryMovements()->count());
    }

    public function test_low_stock_flag(): void
    {
        $this->assertTrue($this->offering(2)->isLowStock());   // threshold 3
        $this->assertFalse($this->offering(10)->isLowStock());
        $this->assertFalse($this->offering(0)->isLowStock());  // out of stock != low stock
    }

    public function test_vendor_can_adjust_stock_via_ui(): void
    {
        $admin = User::factory()->create(['role' => 'vendor_admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $admin->assignRole('vendor_admin');
        $this->vendor->users()->attach($admin->id, ['vendor_role' => 'admin', 'joined_at' => now()]);
        $offering = $this->offering(4);

        $this->actingAs($admin)
            ->post(route('vendor.products.inventory.adjust', $offering), ['quantity' => 20, 'note' => 'Stock take'])
            ->assertRedirect();

        $this->assertSame(20, (int) $offering->fresh()->quantity);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $offering->id, 'type' => 'adjustment', 'qty' => 16, 'balance_after' => 20]);
    }

    public function test_vendor_cannot_adjust_another_vendors_stock(): void
    {
        $other = Vendor::create(['name' => 'Other', 'slug' => 'other-' . Str::random(5), 'contact_email' => 'o@x.com', 'status' => 'approved']);
        $admin = User::factory()->create(['role' => 'vendor_admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $admin->assignRole('vendor_admin');
        $other->users()->attach($admin->id, ['vendor_role' => 'admin', 'joined_at' => now()]);
        $offering = $this->offering(4); // belongs to $this->vendor

        $this->actingAs($admin)
            ->post(route('vendor.products.inventory.adjust', $offering), ['quantity' => 99])
            ->assertForbidden();
    }
}
