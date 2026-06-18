<?php

namespace Tests\Feature\Wallet;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Wallet\Models\Payout;
use App\Modules\Wallet\Services\PayoutService;
use App\Modules\Wallet\Services\WalletService;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class); // wallet.payout_minimum = 10
    }

    private function vendorWithBalance(float $amount): Vendor
    {
        $vendor = Vendor::create([
            'name' => 'V', 'slug' => 'v-' . Str::random(5), 'contact_email' => 'v@x.com', 'status' => 'approved',
        ]);
        $wallet = app(WalletService::class)->walletFor($vendor);
        if ($amount > 0) {
            app(WalletService::class)->post($wallet, 'SALE_CREDIT', $amount);
        }
        return $vendor;
    }

    private function admin(): User
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $admin->assignRole('admin');
        return $admin;
    }

    public function test_weekly_batch_includes_eligible_vendors_only(): void
    {
        $rich = $this->vendorWithBalance(50);
        $poor = $this->vendorWithBalance(5); // below minimum 10 → rolls over

        $created = app(PayoutService::class)->generateWeeklyBatch();

        $this->assertCount(1, $created);
        $this->assertDatabaseHas('payouts', ['vendor_id' => $rich->id, 'amount' => 50, 'status' => 'pending']);
        $this->assertDatabaseMissing('payouts', ['vendor_id' => $poor->id]);
    }

    public function test_generate_skips_vendor_with_existing_pending_payout(): void
    {
        $vendor = $this->vendorWithBalance(50);
        app(PayoutService::class)->generateWeeklyBatch();
        $second = app(PayoutService::class)->generateWeeklyBatch();

        $this->assertCount(0, $second);
        $this->assertSame(1, Payout::where('vendor_id', $vendor->id)->count());
    }

    public function test_approval_posts_payout_debit_and_reduces_balance(): void
    {
        $vendor = $this->vendorWithBalance(50);
        $payout = app(PayoutService::class)->generateWeeklyBatch()->first();

        app(PayoutService::class)->approve($payout, $this->admin());

        $this->assertSame('approved', $payout->fresh()->status);
        $this->assertSame(0.0, (float) app(WalletService::class)->walletFor($vendor)->cached_balance);
        $this->assertDatabaseHas('wallet_ledger_entries', ['type' => 'PAYOUT', 'amount' => 50, 'source_id' => $payout->id]);
    }

    public function test_admin_can_generate_and_approve_via_http(): void
    {
        $vendor = $this->vendorWithBalance(40);

        $this->actingAs($this->admin())->post(route('admin.payouts.generate'))->assertRedirect();
        $payout = Payout::where('vendor_id', $vendor->id)->firstOrFail();

        $this->actingAs($this->admin())->post(route('admin.payouts.approve', $payout))->assertRedirect();

        $this->assertSame('approved', $payout->fresh()->status);
        $this->assertSame(0.0, (float) app(WalletService::class)->walletFor($vendor)->cached_balance);
    }
}
