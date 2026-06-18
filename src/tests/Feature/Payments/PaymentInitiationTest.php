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

class PaymentInitiationTest extends TestCase
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
            'buyer_name' => 'X', 'buyer_email' => 'x@x.com', 'buyer_phone' => '1',
            'buyer_address' => 'a', 'buyer_city' => 'Harare',
            'vendor_id' => $vendor->id, 'fulfilment_track' => 'fbs', 'payment_method' => 'prepaid',
            'status' => 'pending_payment', 'subtotal' => 100, 'delivery_fee' => 5, 'total' => 105,
        ]);
    }

    private function encrypt(array $data): string
    {
        return app(PesepayClient::class)->encrypt($data);
    }

    public function test_initiation_creates_payment_and_redirects_to_gateway(): void
    {
        Http::fake([
            '*v1/payments/initiate' => Http::response([
                'payload' => $this->encrypt([
                    'referenceNumber' => 'R9',
                    'redirectUrl'     => 'https://pay.pesepay.test/r9',
                    'pollUrl'         => 'https://poll.pesepay.test/r9',
                ]),
            ], 200),
        ]);

        $order = $this->prepaidOrder();

        $this->post(route('payments.initiate', $order))
            ->assertRedirect('https://pay.pesepay.test/r9');

        $this->assertDatabaseHas('payments', [
            'order_id'    => $order->id,
            'gateway_ref' => 'R9',
            'status'      => 'pending',
        ]);
    }

    public function test_return_rechecks_status_server_side_and_marks_paid(): void
    {
        Http::fake([
            '*v1/payments/check-payment*' => Http::response([
                'payload' => $this->encrypt(['referenceNumber' => 'R10', 'transactionStatus' => 'SUCCESS']),
            ], 200),
        ]);

        $order = $this->prepaidOrder();
        Payment::create([
            'order_id' => $order->id, 'merchant_reference' => 'M10', 'gateway_ref' => 'R10',
            'amount' => 105, 'currency' => 'ZWL', 'status' => 'pending',
        ]);

        // The browser return must NOT be trusted alone — status comes from the re-check.
        $this->get(route('payments.return', $order))
            ->assertOk()
            ->assertSee('Payment received');

        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_cannot_initiate_payment_for_non_prepaid_order(): void
    {
        $order = $this->prepaidOrder();
        $order->update(['payment_method' => 'cod', 'status' => 'cod_pending']);

        $this->post(route('payments.initiate', $order))
            ->assertRedirect(route('checkout.complete'));

        $this->assertDatabaseMissing('payments', ['order_id' => $order->id]);
    }
}
