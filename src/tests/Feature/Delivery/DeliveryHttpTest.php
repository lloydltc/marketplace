<?php

namespace Tests\Feature\Delivery;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Services\DeliveryService;
use App\Modules\Orders\Models\Order;
use App\Modules\Wallet\Services\WalletService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeliveryHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function rider(): User
    {
        /** @var User $rider */
        $rider = User::factory()->create(['role' => 'rider', 'email_verified_at' => now()]);
        $rider->assignRole('rider');
        return $rider;
    }

    private function admin(): User
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $admin->assignRole('admin');
        return $admin;
    }

    private function fbsOrder(string $payment): Order
    {
        $vendor = Vendor::create([
            'name' => 'V', 'slug' => 'v-' . Str::random(5), 'contact_email' => 'v@x.com', 'status' => 'approved',
        ]);
        $order = Order::create([
            'buyer_name' => 'X', 'buyer_email' => 'x@x.com', 'buyer_phone' => '1',
            'buyer_address' => 'a', 'buyer_city' => 'Harare',
            'vendor_id' => $vendor->id, 'fulfilment_track' => 'fbs', 'payment_method' => $payment,
            'status' => $payment === 'cod' ? 'cod_pending' : 'paid',
            'subtotal' => 100, 'delivery_fee' => 5, 'total' => 105,
            'commission_rate_applied' => 10, 'commission_amount' => 10, 'net_to_vendor' => 90,
        ]);
        $order->transitionTo('processing');
        return $order;
    }

    public function test_admin_can_assign_a_rider(): void
    {
        $order = $this->fbsOrder('prepaid');
        $rider = $this->rider();

        $this->actingAs($this->admin())
            ->post(route('admin.dispatch.assign', $order), ['rider_id' => $rider->id])
            ->assertRedirect();

        $this->assertSame('awaiting_pickup', $order->fresh()->status);
        $this->assertDatabaseHas('deliveries', ['order_id' => $order->id, 'rider_id' => $rider->id, 'status' => 'assigned']);
    }

    public function test_rider_pickup_and_deliver_flow_via_http(): void
    {
        $order    = $this->fbsOrder('prepaid');
        $rider    = $this->rider();
        $delivery = app(DeliveryService::class)->assignRider($order, $rider);

        $this->actingAs($rider)->post(route('rider.deliveries.pickup', $delivery))->assertRedirect();
        $this->assertSame('out_for_delivery', $order->fresh()->status);

        $this->actingAs($rider)->post(route('rider.deliveries.deliver', $delivery), ['proof_note' => 'Left with guard'])
            ->assertRedirect();
        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_rider_cannot_act_on_another_riders_delivery(): void
    {
        $order    = $this->fbsOrder('prepaid');
        $delivery = app(DeliveryService::class)->assignRider($order, $this->rider());

        $this->actingAs($this->rider())->post(route('rider.deliveries.pickup', $delivery))->assertForbidden();
    }

    public function test_admin_reconcile_settles_fbs_cod_order(): void
    {
        $order = $this->fbsOrder('cod');
        $rider = $this->rider();

        $service  = app(DeliveryService::class);
        $delivery = $service->assignRider($order, $rider);
        $service->pickUp($delivery->fresh());
        $service->markDelivered($delivery->fresh(), 105.0);

        $session = Delivery::find($delivery->id)->cashSession;

        // Not settled yet.
        $this->assertSame(0.0, (float) app(WalletService::class)->walletFor($order->vendor)->cached_balance);

        $this->actingAs($this->admin())
            ->post(route('admin.cash-sessions.reconcile', $session), ['collected_total' => 105])
            ->assertRedirect();

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(90.0, (float) app(WalletService::class)->walletFor($order->vendor)->cached_balance);
    }
}
