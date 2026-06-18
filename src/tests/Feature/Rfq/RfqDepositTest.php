<?php

namespace Tests\Feature\Rfq;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Payments\Services\PesepayClient;
use App\Modules\Rfq\Models\PartRequest;
use App\Modules\Rfq\Models\Quote;
use App\Modules\Rfq\Models\RfqDeposit;
use App\Modules\Rfq\Services\RfqService;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RfqDepositTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function requestWithDeposit(string $ref = 'DEP1'): RfqDeposit
    {
        /** @var User $buyer */
        $buyer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $request = PartRequest::create([
            'buyer_user_id' => $buyer->id, 'part_description' => 'Engine', 'location' => 'Harare',
            'estimated_value' => 1000, 'status' => 'open', 'moderation_status' => 'approved',
        ]);

        return RfqDeposit::create([
            'part_request_id' => $request->id, 'buyer_user_id' => $buyer->id,
            'amount' => 50, 'currency' => 'ZWL', 'merchant_reference' => 'M-' . Str::random(5),
            'gateway_ref' => $ref, 'status' => 'pending',
        ]);
    }

    private function payload(array $data): string
    {
        return app(PesepayClient::class)->encrypt($data);
    }

    public function test_deposit_webhook_marks_paid(): void
    {
        $deposit = $this->requestWithDeposit('DEP1');

        $this->postJson(route('payments.webhook'), [
            'payload' => $this->payload(['referenceNumber' => 'DEP1', 'transactionStatus' => 'SUCCESS']),
        ])->assertOk();

        $this->assertSame('paid', $deposit->fresh()->status);
    }

    public function test_deposit_webhook_is_idempotent(): void
    {
        $deposit = $this->requestWithDeposit('DEP2');
        $body = ['payload' => $this->payload(['referenceNumber' => 'DEP2', 'transactionStatus' => 'SUCCESS'])];

        $this->postJson(route('payments.webhook'), $body)->assertOk();
        $paidAt = $deposit->fresh()->paid_at;
        $this->postJson(route('payments.webhook'), $body)->assertOk();

        $this->assertSame('paid', $deposit->fresh()->status);
        $this->assertEquals($paidAt->toDateTimeString(), $deposit->fresh()->paid_at->toDateTimeString());
    }

    public function test_refund_path_when_buyer_closes_request(): void
    {
        $deposit = $this->requestWithDeposit('DEP3');
        $deposit->update(['status' => 'paid', 'paid_at' => now()]);

        app(RfqService::class)->close($deposit->partRequest);

        $this->assertSame('refunded', $deposit->fresh()->status);
        $this->assertSame('closed', $deposit->partRequest->fresh()->status);
    }

    public function test_deposit_credited_on_conversion(): void
    {
        $deposit = $this->requestWithDeposit('DEP4');
        $deposit->update(['status' => 'paid', 'paid_at' => now()]);
        $request = $deposit->partRequest;

        $vendor = Vendor::create([
            'name' => 'V', 'slug' => 'v-' . Str::random(5), 'contact_email' => 'v@x.com', 'status' => 'approved',
        ]);
        $quote = Quote::create([
            'part_request_id' => $request->id, 'vendor_id' => $vendor->id,
            'price' => 800, 'condition' => 'used', 'status' => 'active',
        ]);

        app(RfqService::class)->acceptQuote($request, $quote, [
            'full_name' => 'B', 'email' => 'b@x.com', 'phone' => '1', 'address' => 'a', 'city' => 'Harare',
        ]);

        $this->assertSame('credited', $deposit->fresh()->status);
        $this->assertSame('converted', $request->fresh()->status);
    }
}
