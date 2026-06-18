<?php

namespace Tests\Feature\Wallet;

use App\Models\Vendor;
use App\Modules\Payments\Services\PesepayClient;
use App\Modules\Wallet\Models\WalletLedgerEntry;
use App\Modules\Wallet\Models\WalletTopUp;
use App\Modules\Wallet\Services\WalletService;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TopUpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function payload(array $data): string
    {
        return app(PesepayClient::class)->encrypt($data);
    }

    public function test_topup_webhook_credits_wallet_and_reinstates_cod(): void
    {
        $vendor = Vendor::create([
            'name' => 'V', 'slug' => 'v-' . Str::random(5), 'contact_email' => 'v@x.com',
            'status' => 'approved', 'cod_eligible' => true,
        ]);
        $wallet = app(WalletService::class)->walletFor($vendor);

        // Drive below floor → COD revoked.
        app(WalletService::class)->post($wallet, 'COMMISSION_DEBIT', 10);
        $this->assertFalse($vendor->fresh()->cod_eligible);

        $topUp = WalletTopUp::create([
            'vendor_id' => $vendor->id, 'merchant_reference' => 'TOP-' . Str::random(5),
            'gateway_ref' => 'TOPREF1', 'amount' => 25, 'currency' => 'ZWL', 'status' => 'pending',
        ]);

        $this->postJson(route('payments.webhook'), [
            'payload' => $this->payload(['referenceNumber' => 'TOPREF1', 'transactionStatus' => 'SUCCESS']),
        ])->assertOk();

        $this->assertSame('paid', $topUp->fresh()->status);
        $this->assertSame(15.0, (float) $wallet->fresh()->cached_balance); // -10 + 25
        $this->assertTrue($vendor->fresh()->cod_eligible);                 // reinstated
        $this->assertDatabaseHas('wallet_ledger_entries', ['type' => 'TOP_UP', 'amount' => 25, 'source_id' => $topUp->id]);
    }

    public function test_topup_webhook_is_idempotent(): void
    {
        $vendor = Vendor::create([
            'name' => 'V', 'slug' => 'v-' . Str::random(5), 'contact_email' => 'v@x.com', 'status' => 'approved',
        ]);
        $topUp = WalletTopUp::create([
            'vendor_id' => $vendor->id, 'merchant_reference' => 'TOP-' . Str::random(5),
            'gateway_ref' => 'TOPREF2', 'amount' => 30, 'currency' => 'ZWL', 'status' => 'pending',
        ]);

        $body = ['payload' => $this->payload(['referenceNumber' => 'TOPREF2', 'transactionStatus' => 'SUCCESS'])];

        $this->postJson(route('payments.webhook'), $body)->assertOk();
        $this->postJson(route('payments.webhook'), $body)->assertOk(); // replay

        $this->assertSame(1, WalletLedgerEntry::where('source_id', $topUp->id)->where('type', 'TOP_UP')->count());
        $this->assertSame(30.0, (float) app(WalletService::class)->walletFor($vendor)->cached_balance);
    }
}
