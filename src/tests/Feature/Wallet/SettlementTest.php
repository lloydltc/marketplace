<?php

namespace Tests\Feature\Wallet;

use App\Models\Vendor;
use App\Modules\Orders\Models\Order;
use App\Modules\Wallet\Models\WalletLedgerEntry;
use App\Modules\Wallet\Services\SettlementService;
use App\Modules\Wallet\Services\WalletService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettlementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function order(string $track, string $payment, string $status = 'delivered'): Order
    {
        $vendor = Vendor::create([
            'name' => 'V', 'slug' => 'v-' . Str::random(5), 'contact_email' => 'v@x.com', 'status' => 'approved',
        ]);

        return Order::create([
            'buyer_name' => 'X', 'buyer_email' => 'x@x.com', 'buyer_phone' => '1',
            'buyer_address' => 'a', 'buyer_city' => 'Harare',
            'vendor_id' => $vendor->id, 'fulfilment_track' => $track, 'payment_method' => $payment,
            'status' => $status, 'subtotal' => 100, 'delivery_fee' => 5, 'total' => 105,
            'commission_rate_applied' => 10, 'commission_amount' => 10, 'net_to_vendor' => 90,
        ]);
    }

    private function balance(Order $order): float
    {
        return (float) app(WalletService::class)->walletFor($order->vendor)->cached_balance;
    }

    public function test_completion_credits_net_proceeds_for_platform_collected_order(): void
    {
        // Real listener runs (events not faked) → SALE_CREDIT of net_to_vendor.
        $order = $this->order('fbs', 'prepaid', 'delivered');
        $order->transitionTo('completed');

        $this->assertSame(90.0, $this->balance($order));
        $this->assertDatabaseHas('wallet_ledger_entries', [
            'type' => 'SALE_CREDIT', 'amount' => 90, 'source_id' => $order->id,
        ]);
    }

    public function test_vf_cod_completion_debits_commission(): void
    {
        $order = $this->order('vendor', 'cod', 'delivered');
        $order->transitionTo('completed');

        $this->assertSame(-10.0, $this->balance($order));
        $this->assertDatabaseHas('wallet_ledger_entries', [
            'type' => 'COMMISSION_DEBIT', 'amount' => 10, 'source_id' => $order->id,
        ]);
    }

    public function test_duplicate_settlement_moves_no_money_twice(): void
    {
        $order = $this->order('fbs', 'prepaid', 'delivered');

        // Settle directly twice (simulating a duplicated completion event).
        app(SettlementService::class)->settle($order);
        app(SettlementService::class)->settle($order);

        $this->assertSame(90.0, $this->balance($order));
        $this->assertSame(1, WalletLedgerEntry::where('source_id', $order->id)->where('type', 'SALE_CREDIT')->count());
    }

    public function test_refund_reversal_mirrors_original_credit(): void
    {
        $order = $this->order('fbs', 'prepaid', 'delivered');
        app(SettlementService::class)->settle($order);   // +90
        app(SettlementService::class)->reverse($order);  // -90

        $this->assertSame(0.0, $this->balance($order));
    }
}
