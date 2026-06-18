<?php

namespace Tests\Feature\Wallet;

use App\Models\Vendor;
use App\Modules\Wallet\Exceptions\WalletBelowFloorException;
use App\Modules\Wallet\Services\WalletService;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FloorEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class); // wallet.floor = 0
    }

    private function vendor(array $attrs = []): Vendor
    {
        return Vendor::create(array_merge([
            'name' => 'V', 'slug' => 'v-' . Str::random(5), 'contact_email' => 'v@x.com', 'status' => 'approved',
        ], $attrs));
    }

    private function wallet(): WalletService
    {
        return app(WalletService::class);
    }

    public function test_vendor_above_floor_can_list(): void
    {
        $vendor = $this->vendor();
        $this->wallet()->post($this->wallet()->walletFor($vendor), 'SALE_CREDIT', 50);

        // Does not throw.
        $this->wallet()->assertCanList($vendor);
        $this->assertTrue($this->wallet()->isAboveFloor($vendor));
    }

    public function test_below_floor_blocks_new_listings(): void
    {
        $vendor = $this->vendor();
        // A VF-COD commission debit with no prior credit drives the balance negative.
        $this->wallet()->post($this->wallet()->walletFor($vendor), 'COMMISSION_DEBIT', 10);

        $this->assertFalse($this->wallet()->isAboveFloor($vendor));

        $this->expectException(WalletBelowFloorException::class);
        $this->wallet()->assertCanList($vendor);
    }

    public function test_below_floor_revokes_cod_eligibility(): void
    {
        $vendor = $this->vendor(['cod_eligible' => true]);

        $this->wallet()->post($this->wallet()->walletFor($vendor), 'COMMISSION_DEBIT', 10);

        $this->assertFalse($vendor->fresh()->cod_eligible);
    }

    public function test_topup_restores_standing_within_one_request(): void
    {
        $vendor = $this->vendor(['cod_eligible' => true]);
        $wallet = $this->wallet()->walletFor($vendor);

        $this->wallet()->post($wallet, 'COMMISSION_DEBIT', 10); // -10, below floor, COD revoked
        $this->assertFalse($vendor->fresh()->cod_eligible);

        $this->wallet()->post($wallet, 'TOP_UP', 25); // +25 → +15, back above floor

        $this->assertTrue($this->wallet()->isAboveFloor($vendor));
        $this->assertTrue($vendor->fresh()->cod_eligible);
        $this->wallet()->assertCanList($vendor); // no longer throws
    }
}
