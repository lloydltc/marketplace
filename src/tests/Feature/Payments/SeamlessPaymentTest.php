<?php

namespace Tests\Feature\Payments;

use App\Models\Vendor;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Services\PesepayClient;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeamlessPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function prepaidOrder(): Order
    {
        $vendor = Vendor::create([
            'name' => 'V', 'slug' => 'v-' . Str::random(5), 'contact_email' => 'v@x.com', 'status' => 'approved',
        ]);

        return Order::create([
            'buyer_name' => 'Tatenda', 'buyer_email' => 't@x.com', 'buyer_phone' => '0770000000',
            'buyer_address' => 'a', 'buyer_city' => 'Harare',
            'vendor_id' => $vendor->id, 'fulfilment_track' => 'fbs', 'payment_method' => 'prepaid',
            'status' => 'pending_payment', 'subtotal' => 100, 'delivery_fee' => 5, 'total' => 105,
        ]);
    }

    private function fakeMakePayment(): void
    {
        Http::fake([
            '*v2/payments/make-payment' => Http::response([
                'payload' => app(PesepayClient::class)->encrypt([
                    'referenceNumber' => 'SEAM1',
                    'pollUrl'         => 'https://poll.pesepay.test/seam1',
                ]),
            ], 200),
        ]);
    }

    /**
     * Decrypt the payload that was POSTed to the gateway so we can assert on it.
     */
    private function sentBody(): array
    {
        $decoded = [];
        Http::assertSent(function ($request) use (&$decoded) {
            if (str_contains($request->url(), 'make-payment')) {
                $decoded = app(PesepayClient::class)->decrypt($request['payload']);
                return true;
            }
            return false;
        });

        return $decoded;
    }

    public function test_ecocash_seamless_sends_method_code_and_phone(): void
    {
        $this->fakeMakePayment();
        $order = $this->prepaidOrder();

        $this->post(route('payments.seamless', $order), ['method' => 'ecocash', 'phone' => '0771234567'])
            ->assertRedirect(route('payments.return', ['order' => $order->id]));

        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame('ecocash', $payment->method);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('SEAM1', $payment->gateway_ref);

        $body = $this->sentBody();
        $this->assertSame('PZW211', $body['paymentMethodCode']);
        $this->assertSame('0771234567', $body['paymentMethodRequiredFields']['customerPhoneNumber']);
        $this->assertEquals(105.0, $body['amountDetails']['amount']);
    }

    public function test_innbucks_seamless_sends_no_required_field(): void
    {
        $this->fakeMakePayment();
        $order = $this->prepaidOrder();

        $this->post(route('payments.seamless', $order), ['method' => 'innbucks'])
            ->assertRedirect(route('payments.return', ['order' => $order->id]));

        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertSame('innbucks', $payment->method);

        $body = $this->sentBody();
        $this->assertSame('PZW212', $body['paymentMethodCode']);
        $this->assertSame([], (array) $body['paymentMethodRequiredFields']);
    }

    public function test_ecocash_requires_a_phone_number(): void
    {
        $order = $this->prepaidOrder();

        $this->post(route('payments.seamless', $order), ['method' => 'ecocash'])
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('payments', ['order_id' => $order->id]);
    }

    public function test_seamless_rejected_for_non_prepaid_order(): void
    {
        $order = $this->prepaidOrder();
        $order->update(['payment_method' => 'cod', 'status' => 'cod_pending']);

        $this->post(route('payments.seamless', $order), ['method' => 'innbucks'])
            ->assertRedirect(route('checkout.complete'));
    }

    public function test_webhook_confirms_a_seamless_payment(): void
    {
        $this->fakeMakePayment();
        $order = $this->prepaidOrder();
        $this->post(route('payments.seamless', $order), ['method' => 'ecocash', 'phone' => '0771234567']);

        // Buyer approves on phone → Pesepay posts the result webhook.
        $this->postJson(route('payments.webhook'), [
            'payload' => app(PesepayClient::class)->encrypt([
                'referenceNumber' => 'SEAM1', 'transactionStatus' => 'SUCCESS',
            ]),
        ])->assertOk();

        $this->assertSame('paid', $order->fresh()->status);
    }
}
