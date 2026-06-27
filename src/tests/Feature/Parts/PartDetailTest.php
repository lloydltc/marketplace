<?php

namespace Tests\Feature\Parts;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Orders\Models\Order;
use App\Modules\Parts\Models\Part;
use App\Modules\Parts\Services\CoPurchaseService;
use App\Modules\Products\Models\Product;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PM5: part detail — offers compare, alternatives, warranty/guides, and
 * deterministic frequently-bought-together.
 */
class PartDetailTest extends TestCase
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
        $this->category = Category::create(['name' => 'Brakes', 'slug' => 'brakes-' . Str::random(4), 'sort_order' => 0]);
    }

    private function part(string $name, array $attrs = []): Part
    {
        return Part::create(array_merge(['name' => $name, 'category_id' => $this->category->id], $attrs));
    }

    private function offer(Part $part, float $price, int $qty = 5, ?Vendor $vendor = null): Product
    {
        return Product::create([
            'vendor_id' => ($vendor ?? $this->vendor)->id, 'part_id' => $part->id, 'category_id' => $this->category->id,
            'title' => $part->name, 'description' => 'x', 'price_zwl' => $price * 10, 'price_usd' => $price,
            'quantity' => $qty, 'status' => 'active',
        ]);
    }

    public function test_detail_shows_offers_lowest_first_with_warranty(): void
    {
        $part = $this->part('Brake Pads', ['warranty_months' => 12, 'warranty_terms' => 'Manufacturer defects']);
        $v2 = Vendor::create(['name' => 'Cheaper Co', 'slug' => 'cc-' . Str::random(5), 'contact_email' => 'c@x.com', 'status' => 'approved']);
        $this->offer($part, 30);
        $this->offer($part, 22, 5, $v2);

        $res = $this->get(route('parts.show', $part->slug))->assertOk();
        $res->assertSee('Seller offers');
        $res->assertSee('USD 22.00');
        $res->assertSee('USD 30.00');
        $res->assertSee('Lowest');
        $res->assertSee('12-month warranty');
    }

    public function test_alternatives_combine_curated_and_oem(): void
    {
        $part = $this->part('OEM Filter');
        $this->offer($part, 10);
        $curated = $this->part('Curated Substitute');
        $this->offer($curated, 9);
        $part->alternatives()->create(['alternative_part_id' => $curated->id, 'relation' => 'substitute']);

        $oemShared = $this->part('OEM-Shared Filter');
        $this->offer($oemShared, 8);
        $part->oemNumbers()->create(['number' => 'SHARED-1', 'type' => 'oem']);
        $oemShared->oemNumbers()->create(['number' => 'SHARED-1', 'type' => 'aftermarket']);

        $this->get(route('parts.show', $part->slug))
            ->assertOk()
            ->assertSee('Alternatives')
            ->assertSee('Curated Substitute')
            ->assertSee('OEM-Shared Filter');
    }

    public function test_frequently_bought_together_uses_co_purchase_counts(): void
    {
        $pads = $this->part('Brake Pads');
        $fluid = $this->part('Brake Fluid');
        $unrelated = $this->part('Wiper Blade');
        $padsOffer = $this->offer($pads, 30);
        $fluidOffer = $this->offer($fluid, 8);
        $this->offer($unrelated, 5);

        // Two orders bought pads + fluid together.
        foreach (range(1, 2) as $n) {
            $order = Order::create([
                'vendor_id' => $this->vendor->id, 'buyer_name' => 'B', 'buyer_phone' => '07', 'buyer_email' => "b{$n}@x.com",
                'buyer_address' => '1 Main St', 'buyer_city' => 'Harare',
                'fulfilment_track' => 'vendor', 'payment_method' => 'cod', 'status' => 'cod_pending',
                'currency' => 'ZWL', 'subtotal' => 380, 'delivery_fee' => 0, 'total' => 380,
                'commission_rate_applied' => 10, 'commission_amount' => 38, 'net_to_vendor' => 342,
            ]);
            $order->items()->create(['product_id' => $padsOffer->id, 'title' => 'Brake Pads', 'unit_price' => 30, 'quantity' => 1, 'line_total' => 30]);
            $order->items()->create(['product_id' => $fluidOffer->id, 'title' => 'Brake Fluid', 'unit_price' => 8, 'quantity' => 1, 'line_total' => 8]);
        }

        $fbt = app(CoPurchaseService::class)->frequentlyBoughtWith($pads)->pluck('name');
        $this->assertTrue($fbt->contains('Brake Fluid'));
        $this->assertFalse($fbt->contains('Wiper Blade'));
        $this->assertFalse($fbt->contains('Brake Pads')); // excludes self

        $this->get(route('parts.show', $pads->slug))
            ->assertOk()
            ->assertSee('Frequently bought together')
            ->assertSee('Brake Fluid');
    }

    public function test_inactive_part_is_not_found(): void
    {
        $part = $this->part('Hidden', ['status' => 'inactive']);
        $this->offer($part, 10);

        $this->get(route('parts.show', $part->slug))->assertNotFound();
    }
}
