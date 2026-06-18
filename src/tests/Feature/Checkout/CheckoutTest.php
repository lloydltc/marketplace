<?php

namespace Tests\Feature\Checkout;

use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Products\Models\Product;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function settings(): SettingsService
    {
        return app(SettingsService::class);
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
            'title' => 'Item ' . Str::random(4), 'description' => 'desc',
            'price_zwl' => 100, 'quantity' => 10, 'status' => 'active',
        ], $attrs));
    }

    private function validCustomer(): array
    {
        return [
            'full_name' => 'Tendai Moyo',
            'email'     => 'tendai@example.com',
            'phone'     => '0772000000',
            'address'   => '1 Samora Machel Ave',
            'city'      => 'Harare',
        ];
    }

    // ─── Access ─────────────────────────────────────────────────────────────────

    public function test_empty_cart_redirects_to_cart(): void
    {
        $this->get(route('checkout.show'))->assertRedirect(route('cart.index'));
    }

    public function test_guest_can_reach_checkout_with_items(): void
    {
        $product = $this->product();
        $this->post(route('cart.add'), ['product_id' => $product->id]);

        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertSee($product->vendor->name);
    }

    // ─── Valid checkout ─────────────────────────────────────────────────────────

    public function test_valid_prepaid_checkout_reaches_payment_with_settings_delivery_fee(): void
    {
        $this->settings()->set('delivery.fbs_default_fee', 9.00);

        $product = $this->product(['fulfilment_type' => 'fbs', 'price_zwl' => 100]);
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2]);

        $key = $product->vendor_id . '|fbs';

        $this->post(route('checkout.store'), array_merge($this->validCustomer(), [
            'fulfilment' => [$key => 'fbs'],
            'payment'    => [$key => 'prepaid'],
        ]))->assertRedirect(route('checkout.payment'));

        // Payment page reflects subtotal (200) + settings delivery (9) = 209.
        $this->get(route('checkout.payment'))
            ->assertOk()
            ->assertSee('209.00')
            ->assertSee('9.00');
    }

    public function test_checkout_requires_contact_fields(): void
    {
        $product = $this->product();
        $this->post(route('cart.add'), ['product_id' => $product->id]);

        $this->post(route('checkout.store'), [])
            ->assertSessionHasErrors(['full_name', 'email', 'phone', 'address', 'city']);
    }

    // ─── Server-side COD enforcement (BUSINESS_MODEL.md §3) ──────────────────────

    public function test_ineligible_cod_is_rejected_server_side(): void
    {
        // FBS COD switched OFF — COD must be impossible even if the form posts it.
        $this->settings()->set('cod.fbs_enabled', false);

        $product = $this->product(['fulfilment_type' => 'fbs', 'cod_allowed' => true]);
        $this->post(route('cart.add'), ['product_id' => $product->id]);

        $key = $product->vendor_id . '|fbs';

        $this->post(route('checkout.store'), array_merge($this->validCustomer(), [
            'fulfilment' => [$key => 'fbs'],
            'payment'    => [$key => 'cod'],
        ]))->assertSessionHasErrors('checkout');

        // Did not advance to payment.
        $this->get(route('checkout.payment'))->assertRedirect(route('checkout.show'));
    }

    public function test_vf_cod_rejected_when_vendor_not_eligible(): void
    {
        $this->settings()->set('cod.vf_enabled', true);

        $product = $this->product(
            ['fulfilment_type' => 'vendor', 'cod_allowed' => true],
            ['cod_eligible' => false]
        );
        $this->post(route('cart.add'), ['product_id' => $product->id]);

        $key = $product->vendor_id . '|vendor';

        $this->post(route('checkout.store'), array_merge($this->validCustomer(), [
            'fulfilment' => [$key => 'vendor'],
            'payment'    => [$key => 'cod'],
        ]))->assertSessionHasErrors('checkout');
    }

    public function test_vf_cod_accepted_when_vendor_eligible(): void
    {
        $this->settings()->set('cod.vf_enabled', true);

        $product = $this->product(
            ['fulfilment_type' => 'vendor', 'cod_allowed' => true],
            ['cod_eligible' => true]
        );
        $this->post(route('cart.add'), ['product_id' => $product->id]);

        $key = $product->vendor_id . '|vendor';

        $this->post(route('checkout.store'), array_merge($this->validCustomer(), [
            'fulfilment' => [$key => 'vendor'],
            'payment'    => [$key => 'cod'],
        ]))->assertRedirect(route('checkout.payment'));

        $this->get(route('checkout.payment'))->assertOk()->assertSee('Cash on delivery');
    }
}
