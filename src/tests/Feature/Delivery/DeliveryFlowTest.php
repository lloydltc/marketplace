<?php

namespace Tests\Feature\Delivery;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Services\CashReconciliationService;
use App\Modules\Delivery\Services\DeliveryService;
use App\Modules\Orders\Models\Order;
use App\Modules\Wallet\Services\WalletService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeliveryFlowTest extends TestCase
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

    /** FBS order already advanced to "processing", ready to dispatch. */
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

    private function balance(Order $order): float
    {
        return (float) app(WalletService::class)->walletFor($order->vendor)->cached_balance;
    }

    private function deliver(Order $order, ?float $cod = null): Delivery
    {
        $service  = app(DeliveryService::class);
        $delivery = $service->assignRider($order, $this->rider());
        $service->pickUp($delivery->fresh());
        $service->markDelivered($delivery->fresh(), $cod);

        return $delivery->fresh();
    }

    // ─── Lifecycle feeds the order state machine ────────────────────────────────

    public function test_rider_actions_advance_the_order_through_fbs_states(): void
    {
        $order   = $this->fbsOrder('prepaid');
        $service = app(DeliveryService::class);

        $delivery = $service->assignRider($order, $this->rider());
        $this->assertSame('awaiting_pickup', $order->fresh()->status);
        $this->assertSame('assigned', $delivery->status);

        $service->pickUp($delivery->fresh());
        $this->assertSame('out_for_delivery', $order->fresh()->status);

        $service->markDelivered($delivery->fresh());
        $this->assertSame('completed', $order->fresh()->status); // prepaid auto-completes
    }

    // ─── Prepaid FBS settles on delivery ────────────────────────────────────────

    public function test_prepaid_fbs_settles_on_delivery(): void
    {
        $order = $this->fbsOrder('prepaid');
        $this->deliver($order);

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(90.0, $this->balance($order)); // SALE_CREDIT of net_to_vendor
    }

    // ─── KEY CRITERION: FBS-COD settles ONLY after cash reconciliation ──────────

    public function test_fbs_cod_does_not_settle_until_cash_is_reconciled(): void
    {
        $order    = $this->fbsOrder('cod');
        $delivery = $this->deliver($order, 105.0);

        // Delivered, but NOT completed and NOT settled — cash is with the rider.
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame(0.0, $this->balance($order));
        $this->assertNotNull($delivery->cash_session_id);
        $this->assertSame(105.0, (float) $delivery->cashSession->expected_total);

        // Rider hands in the exact cash → reconcile → order completes → settles.
        app(CashReconciliationService::class)->reconcile($delivery->cashSession, 105.0, $this->adminLikeRider($delivery));

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(90.0, $this->balance($order));
    }

    public function test_cash_discrepancy_blocks_settlement_until_resolved(): void
    {
        $order    = $this->fbsOrder('cod');
        $delivery = $this->deliver($order, 105.0);
        $session  = $delivery->cashSession;
        $admin    = $this->adminLikeRider($delivery);

        // Short by 5 → flagged, nothing settles.
        app(CashReconciliationService::class)->reconcile($session, 100.0, $admin);
        $this->assertSame('discrepancy', $session->fresh()->status);
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame(0.0, $this->balance($order));

        // Admin resolves → completes + settles.
        app(CashReconciliationService::class)->resolve($session->fresh(), $admin);
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(90.0, $this->balance($order));
    }

    // ─── Delivery fee is platform margin, visible in the snapshot ───────────────

    public function test_delivery_fee_is_retained_by_platform_in_the_snapshot(): void
    {
        $order = $this->fbsOrder('prepaid');
        $this->deliver($order);

        // Buyer paid 105 (100 goods + 5 delivery). Vendor is credited net goods
        // only (90); the 5 delivery fee is the platform's margin.
        $this->assertSame(5.0, (float) $order->fresh()->delivery_fee);
        $this->assertSame(90.0, $this->balance($order));
    }

    private function adminLikeRider(Delivery $delivery): User
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $admin->assignRole('admin');
        return $admin;
    }
}
