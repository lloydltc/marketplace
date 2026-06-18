<?php

namespace Tests\Feature\Orders;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Models\Payment;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
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

    private function order(Vendor $vendor, array $attrs = []): Order
    {
        return Order::create(array_merge([
            'buyer_name' => 'X', 'buyer_email' => 'x@x.com', 'buyer_phone' => '1',
            'buyer_address' => 'a', 'buyer_city' => 'Harare',
            'vendor_id' => $vendor->id, 'fulfilment_track' => 'fbs', 'payment_method' => 'prepaid',
            'status' => 'paid', 'subtotal' => 100, 'delivery_fee' => 5, 'total' => 105,
        ], $attrs));
    }

    private function customer(): User
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $user->assignRole('customer');
        return $user;
    }

    // ─── Buyer ──────────────────────────────────────────────────────────────────

    public function test_buyer_sees_only_their_orders(): void
    {
        [$vendor] = $this->vendorWithAdmin();
        $buyer    = $this->customer();
        $mine     = $this->order($vendor, ['buyer_user_id' => $buyer->id]);
        $theirs   = $this->order($vendor, ['buyer_user_id' => $this->customer()->id]);

        $this->actingAs($buyer)->get(route('orders.index'))
            ->assertOk()
            ->assertSee($mine->order_number)
            ->assertDontSee($theirs->order_number);
    }

    public function test_buyer_cannot_view_another_buyers_order(): void
    {
        [$vendor] = $this->vendorWithAdmin();
        $order    = $this->order($vendor, ['buyer_user_id' => $this->customer()->id]);

        $this->actingAs($this->customer())->get(route('orders.show', $order))->assertForbidden();
    }

    public function test_buyer_can_view_invoice(): void
    {
        [$vendor] = $this->vendorWithAdmin();
        $buyer    = $this->customer();
        $order    = $this->order($vendor, ['buyer_user_id' => $buyer->id]);

        $this->actingAs($buyer)->get(route('orders.invoice', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('SalmaDrive');
    }

    public function test_buyer_cancel_of_paid_order_flags_refund(): void
    {
        [$vendor] = $this->vendorWithAdmin();
        $buyer    = $this->customer();
        $order    = $this->order($vendor, ['buyer_user_id' => $buyer->id, 'status' => 'paid']);
        $payment  = Payment::create([
            'order_id' => $order->id, 'merchant_reference' => 'M-' . Str::random(5),
            'amount' => 105, 'currency' => 'ZWL', 'status' => 'paid',
        ]);

        $this->actingAs($buyer)->post(route('orders.cancel', $order))->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('refunded', $payment->fresh()->status);
    }

    public function test_buyer_cannot_cancel_delivered_order(): void
    {
        [$vendor] = $this->vendorWithAdmin();
        $buyer    = $this->customer();
        $order    = $this->order($vendor, ['buyer_user_id' => $buyer->id, 'status' => 'delivered']);

        $this->actingAs($buyer)->post(route('orders.cancel', $order))->assertSessionHasErrors('order');
        $this->assertSame('delivered', $order->fresh()->status);
    }

    // ─── Vendor ─────────────────────────────────────────────────────────────────

    public function test_vendor_can_advance_their_order(): void
    {
        [$vendor, $admin] = $this->vendorWithAdmin();
        $order = $this->order($vendor, ['status' => 'paid']);

        $this->actingAs($admin)->post(route('vendor.orders.transition', $order), ['to' => 'processing'])
            ->assertRedirect();

        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_vendor_illegal_transition_is_rejected(): void
    {
        [$vendor, $admin] = $this->vendorWithAdmin();
        $order = $this->order($vendor, ['status' => 'paid']);

        $this->actingAs($admin)->post(route('vendor.orders.transition', $order), ['to' => 'delivered'])
            ->assertSessionHasErrors('order');

        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_vendor_cannot_manage_another_vendors_order(): void
    {
        [, $admin]       = $this->vendorWithAdmin();
        [$otherVendor]   = $this->vendorWithAdmin();
        $order           = $this->order($otherVendor);

        $this->actingAs($admin)->get(route('vendor.orders.show', $order))->assertForbidden();
    }
}
