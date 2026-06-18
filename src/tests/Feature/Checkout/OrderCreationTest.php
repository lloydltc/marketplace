<?php

namespace Tests\Feature\Checkout;

use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Orders\Models\Order;
use App\Modules\Products\Models\Product;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function product(array $attrs = [], array $vendorAttrs = []): Product
    {
        $vendor = Vendor::create(array_merge([
            'name' => 'Vendor ' . Str::random(4), 'slug' => 'vendor-' . Str::random(6),
            'contact_email' => 'v@x.com', 'status' => 'approved',
        ], $vendorAttrs));

        $category = Category::create(['name' => 'Parts', 'slug' => 'parts-' . Str::random(6), 'sort_order' => 0]);

        return Product::create(array_merge([
            'vendor_id' => $vendor->id, 'category_id' => $category->id,
            'title' => 'Brake Kit', 'description' => 'desc',
            'price_zwl' => 100, 'quantity' => 10, 'status' => 'active',
        ], $attrs));
    }

    private function customer(): array
    {
        return [
            'full_name' => 'Rudo Chari', 'email' => 'rudo@example.com', 'phone' => '0772000000',
            'address' => '5 Leopold Takawira', 'city' => 'Harare',
        ];
    }

    private function placeOrder(Product $product, string $track, string $payment, int $qty = 2): void
    {
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => $qty]);

        $key = $product->vendor_id . '|' . $track;
        $this->post(route('checkout.store'), array_merge($this->customer(), [
            'fulfilment' => [$key => $track],
            'payment'    => [$key => $payment],
        ]))->assertRedirect(route('checkout.payment'));

        $this->post(route('checkout.place'))->assertRedirect(route('checkout.complete'));
    }

    public function test_prepaid_checkout_creates_order_with_commission_snapshot(): void
    {
        // Vendor default commission_rate is 10% (migration default).
        $product = $this->product(['fulfilment_type' => 'fbs', 'price_zwl' => 100]);

        $this->placeOrder($product, 'fbs', 'prepaid'); // qty 2 → subtotal 200, FBS delivery 5

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame('pending_payment', $order->status);
        $this->assertSame('prepaid', $order->payment_method);
        $this->assertSame(200.0, (float) $order->subtotal);
        $this->assertSame(5.0, (float) $order->delivery_fee);   // delivery.fbs_default_fee
        $this->assertSame(205.0, (float) $order->total);
        $this->assertSame(10.0, (float) $order->commission_rate_applied);
        $this->assertSame(20.0, (float) $order->commission_amount);
        $this->assertSame(180.0, (float) $order->net_to_vendor); // subtotal - commission (delivery excluded)
        $this->assertCount(1, $order->items);

        // Cart cleared, confirmation shows the order.
        $this->get(route('checkout.complete'))->assertOk()->assertSee($order->order_number);
    }

    public function test_cod_checkout_creates_cod_pending_order(): void
    {
        app(\App\Modules\Settings\Services\SettingsService::class)->set('cod.vf_enabled', true);

        $product = $this->product(
            ['fulfilment_type' => 'vendor', 'cod_allowed' => true],
            ['cod_eligible' => true]
        );

        $this->placeOrder($product, 'vendor', 'cod');

        $order = Order::first();
        $this->assertSame('cod_pending', $order->status);
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame(0.0, (float) $order->delivery_fee); // vendor-arranged
    }

    public function test_commission_snapshot_is_immutable_after_rate_change(): void
    {
        $product = $this->product(['fulfilment_type' => 'fbs']);
        $this->placeOrder($product, 'fbs', 'prepaid');

        $order = Order::first();
        $this->assertSame(10.0, (float) $order->commission_rate_applied);

        // Changing the vendor's rate later must not rewrite historical orders.
        $product->vendor->update(['commission_rate' => 5]);

        $this->assertSame(10.0, (float) $order->fresh()->commission_rate_applied);
        $this->assertSame(20.0, (float) $order->fresh()->commission_amount);
    }
}
