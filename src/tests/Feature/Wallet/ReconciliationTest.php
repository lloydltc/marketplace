<?php

namespace Tests\Feature\Wallet;

use App\Models\Vendor;
use App\Modules\Wallet\Models\WalletTopUp;
use App\Modules\Wallet\Services\ReconciliationService;
use App\Modules\Wallet\Services\WalletService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * P4: money reconciliation detects (and the command alarms on) any drift between
 * the ledger, the cached balance, and the gateway top-up records.
 */
class ReconciliationTest extends TestCase
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
            'name' => 'V ' . Str::random(4), 'slug' => 'v-' . Str::random(6),
            'contact_email' => 'v@x.com', 'status' => 'approved',
        ]);
    }

    private function wallet(): WalletService
    {
        return app(WalletService::class);
    }

    private function reconciler(): ReconciliationService
    {
        return app(ReconciliationService::class);
    }

    public function test_clean_books_report_no_discrepancies(): void
    {
        $wallet = $this->wallet()->walletFor($this->vendor());
        $this->wallet()->post($wallet, 'SALE_CREDIT', 100);
        $this->wallet()->post($wallet, 'COMMISSION_DEBIT', 10);

        $report = $this->reconciler()->run();

        $this->assertSame([], $report['discrepancies']);
        $this->assertSame(1, $report['checked']);
    }

    public function test_balance_drift_is_detected(): void
    {
        $vendor = $this->vendor();
        $wallet = $this->wallet()->walletFor($vendor);
        $this->wallet()->post($wallet, 'SALE_CREDIT', 100);

        // Corrupt the cache out-of-band (simulating drift).
        $wallet->forceFill(['cached_balance' => 999.99])->save();

        $report = $this->reconciler()->run();

        $this->assertCount(1, $report['discrepancies']);
        $this->assertSame('balance_drift', $report['discrepancies'][0]['type']);
        $this->assertSame(100.0, $report['discrepancies'][0]['expected']);
    }

    public function test_paid_topup_without_ledger_entry_is_flagged(): void
    {
        $vendor = $this->vendor();
        $this->wallet()->walletFor($vendor); // wallet exists, clean

        // A paid gateway top-up that was never booked to the ledger.
        WalletTopUp::create([
            'vendor_id' => $vendor->id,
            'merchant_reference' => 'MR-' . Str::random(8),
            'gateway_ref' => 'GW-' . Str::random(8),
            'amount' => 50, 'currency' => 'ZWL', 'status' => 'paid',
        ]);

        $report = $this->reconciler()->run();

        $types = array_column($report['discrepancies'], 'type');
        $this->assertContains('unbooked_topup', $types);
    }

    public function test_command_exits_nonzero_on_drift_and_fix_corrects(): void
    {
        $vendor = $this->vendor();
        $wallet = $this->wallet()->walletFor($vendor);
        $this->wallet()->post($wallet, 'SALE_CREDIT', 100);
        $wallet->forceFill(['cached_balance' => 7.00])->save();

        // Read-only run alarms (non-zero).
        $this->artisan('wallet:reconcile')->assertExitCode(1);

        // --fix recomputes the drifted balance from the ledger.
        $this->artisan('wallet:reconcile --fix')->assertExitCode(1);
        $this->assertSame(100.0, (float) $wallet->fresh()->cached_balance);

        // Now clean.
        $this->artisan('wallet:reconcile')->assertExitCode(0);
    }
}
