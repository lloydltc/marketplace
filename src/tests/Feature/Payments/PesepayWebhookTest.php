<?php

namespace Tests\Feature\Payments;

use App\Models\Vendor;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Services\PesepayClient;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PesepayWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function orderWithPayment(string $ref = 'REF1'): Payment
    {
        $vendor = Vendor::create([
            'name' => 'V', 'slug' => 'v-' . Str::random(5), 'contact_email' => 'v@x.com', 'status' => 'approved',
        ]);

        $order = Order::create([
            'buyer_name' => 'X', 'buyer_email' => 'x@x.com', 'buyer_phone' => '1',
            'buyer_address' => 'a', 'buyer_city' => 'Harare',
            'vendor_id' => $vendor->id, 'fulfilment_track' => 'fbs', 'payment_method' => 'prepaid',
            'status' => 'pending_payment', 'subtotal' => 100, 'delivery_fee' => 5, 'total' => 105,
        ]);

        return Payment::create([
            'order_id' => $order->id, 'merchant_reference' => 'M-' . Str::random(6),
            'gateway_ref' => $ref, 'amount' => 105, 'currency' => 'ZWL', 'status' => 'pending',
        ]);
    }

    private function payload(array $data): string
    {
        return app(PesepayClient::class)->encrypt($data);
    }

    public function test_successful_webhook_marks_order_and_payment_paid(): void
    {
        $payment = $this->orderWithPayment('REF1');

        $this->postJson(route('payments.webhook'), [
            'payload' => $this->payload(['referenceNumber' => 'REF1', 'transactionStatus' => 'SUCCESS']),
        ])->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $payment->order->fresh()->status);
        $this->assertNotNull($payment->order->fresh()->paid_at);
    }

    public function test_replayed_webhook_is_idempotent(): void
    {
        $payment = $this->orderWithPayment('REF2');
        $body    = ['payload' => $this->payload(['referenceNumber' => 'REF2', 'transactionStatus' => 'SUCCESS'])];

        $this->postJson(route('payments.webhook'), $body)->assertOk();
        $paidAt = $payment->order->fresh()->paid_at;

        // Replay the exact same webhook — no second state change, no re-timestamp.
        $this->postJson(route('payments.webhook'), $body)->assertOk();

        $order = $payment->order->fresh();
        $this->assertSame('paid', $order->status);
        $this->assertEquals($paidAt->toDateTimeString(), $order->paid_at->toDateTimeString());
        $this->assertSame(1, Payment::where('id', $payment->id)->where('status', 'paid')->count());
    }

    public function test_failed_status_marks_order_failed(): void
    {
        $payment = $this->orderWithPayment('REF3');

        $this->postJson(route('payments.webhook'), [
            'payload' => $this->payload(['referenceNumber' => 'REF3', 'transactionStatus' => 'FAILED']),
        ])->assertOk();

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('failed', $payment->order->fresh()->status);
    }

    public function test_undecryptable_payload_is_rejected(): void
    {
        $this->postJson(route('payments.webhook'), ['payload' => '!!garbage!!'])
            ->assertStatus(400);
    }

    public function test_unknown_reference_is_acknowledged_but_ignored(): void
    {
        $this->postJson(route('payments.webhook'), [
            'payload' => $this->payload(['referenceNumber' => 'DOES-NOT-EXIST', 'transactionStatus' => 'SUCCESS']),
        ])->assertOk();
    }
}
