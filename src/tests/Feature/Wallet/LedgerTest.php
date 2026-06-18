<?php

namespace Tests\Feature\Wallet;

use App\Models\Vendor;
use App\Modules\Wallet\Models\WalletLedgerEntry;
use App\Modules\Wallet\Services\WalletService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class LedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function vendor(): Vendor
    {
        return Vendor::create([
            'name' => 'V', 'slug' => 'v-' . Str::random(5), 'contact_email' => 'v@x.com', 'status' => 'approved',
        ]);
    }

    private function wallet(): WalletService
    {
        return app(WalletService::class);
    }

    public function test_balance_is_derived_from_entries(): void
    {
        $wallet = $this->wallet()->walletFor($this->vendor());

        $this->wallet()->post($wallet, 'SALE_CREDIT', 100);
        $this->wallet()->post($wallet, 'COMMISSION_DEBIT', 10);
        $this->wallet()->post($wallet, 'SALE_CREDIT', 50);

        $this->assertSame(140.0, (float) $wallet->fresh()->cached_balance);
    }

    public function test_cached_balance_always_equals_reconciled_sum(): void
    {
        $wallet = $this->wallet()->walletFor($this->vendor());
        $this->wallet()->post($wallet, 'SALE_CREDIT', 200);
        $this->wallet()->post($wallet, 'PAYOUT', 30);
        $this->wallet()->post($wallet, 'TOP_UP', 25);

        // Independently sum the ledger and compare to the cached balance.
        $signed = $wallet->entries->sum(fn ($e) => $e->signedAmount());
        $this->assertSame(round($signed, 2), (float) $this->wallet()->recalculate($wallet->fresh()));
        $this->assertSame(195.0, (float) $wallet->fresh()->cached_balance);
    }

    public function test_idempotency_key_prevents_double_posting(): void
    {
        $wallet = $this->wallet()->walletFor($this->vendor());

        $this->wallet()->post($wallet, 'SALE_CREDIT', 100, ['idempotency_key' => 'settle:1']);
        $this->wallet()->post($wallet, 'SALE_CREDIT', 100, ['idempotency_key' => 'settle:1']); // replay

        $this->assertSame(1, WalletLedgerEntry::where('wallet_id', $wallet->id)->count());
        $this->assertSame(100.0, (float) $wallet->fresh()->cached_balance);
    }

    public function test_entries_are_append_only(): void
    {
        $wallet = $this->wallet()->walletFor($this->vendor());
        $entry  = $this->wallet()->post($wallet, 'SALE_CREDIT', 100);

        $this->expectException(RuntimeException::class);
        $entry->update(['amount' => 999]);
    }

    public function test_entries_cannot_be_deleted(): void
    {
        $wallet = $this->wallet()->walletFor($this->vendor());
        $entry  = $this->wallet()->post($wallet, 'SALE_CREDIT', 100);

        $this->expectException(RuntimeException::class);
        $entry->delete();
    }
}
