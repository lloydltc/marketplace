<?php

namespace Tests\Feature\Orders;

use App\Models\Vendor;
use App\Modules\Orders\Events\OrderCompletedEvent;
use App\Modules\Orders\Exceptions\IllegalOrderTransitionException;
use App\Modules\Orders\Models\Order;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderStateMachineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function order(string $track = 'fbs', string $status = 'paid', string $payment = 'prepaid'): Order
    {
        $vendor = Vendor::create([
            'name' => 'V', 'slug' => 'v-' . Str::random(5), 'contact_email' => 'v@x.com', 'status' => 'approved',
        ]);

        return Order::create([
            'buyer_name' => 'X', 'buyer_email' => 'x@x.com', 'buyer_phone' => '1',
            'buyer_address' => 'a', 'buyer_city' => 'Harare',
            'vendor_id' => $vendor->id, 'fulfilment_track' => $track, 'payment_method' => $payment,
            'status' => $status, 'subtotal' => 100, 'delivery_fee' => 5, 'total' => 105,
        ]);
    }

    public function test_fbs_happy_path_reaches_completed(): void
    {
        Event::fake([OrderCompletedEvent::class]);
        $order = $this->order('fbs', 'paid');

        $order->transitionTo('processing');
        $order->transitionTo('awaiting_pickup');
        $order->transitionTo('out_for_delivery');
        $order->transitionTo('delivered');
        $order->transitionTo('completed');

        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->completed_at);
        Event::assertDispatched(OrderCompletedEvent::class, 1);
    }

    public function test_vf_happy_path_reaches_completed(): void
    {
        $order = $this->order('vendor', 'paid');

        $order->transitionTo('processing');
        $order->transitionTo('vendor_shipping');
        $order->transitionTo('delivered');
        $order->transitionTo('completed');

        $this->assertSame('completed', $order->status);
    }

    public function test_illegal_transition_is_rejected(): void
    {
        $order = $this->order('fbs', 'paid');

        // Cannot jump straight from paid to delivered.
        $this->expectException(IllegalOrderTransitionException::class);
        $order->transitionTo('delivered');
    }

    public function test_fbs_state_not_allowed_on_vf_track(): void
    {
        $order = $this->order('vendor', 'processing');

        // vendor_shipping is the VF ship state; awaiting_pickup is FBS-only.
        $this->assertFalse($order->canTransitionTo('awaiting_pickup'));
        $this->assertTrue($order->canTransitionTo('vendor_shipping'));
    }

    public function test_settlement_event_fires_exactly_once(): void
    {
        Event::fake([OrderCompletedEvent::class]);
        $order = $this->order('vendor', 'delivered');

        $order->transitionTo('completed');
        $this->assertNotNull($order->settled_at);

        // Completed is terminal — a second completion is rejected, so no 2nd event.
        try {
            $order->transitionTo('completed');
        } catch (IllegalOrderTransitionException) {
            // expected
        }

        Event::assertDispatched(OrderCompletedEvent::class, 1);
    }

    public function test_terminal_states_allow_no_transitions(): void
    {
        $completed = $this->order('fbs', 'completed');
        $cancelled = $this->order('fbs', 'cancelled');

        $this->assertSame([], $completed->allowedTransitions());
        $this->assertSame([], $cancelled->allowedTransitions());
    }

    public function test_can_cancel_before_shipping_but_not_after_delivery(): void
    {
        $this->assertTrue($this->order('fbs', 'processing')->canTransitionTo('cancelled'));
        $this->assertFalse($this->order('fbs', 'delivered')->canTransitionTo('cancelled'));
    }
}
