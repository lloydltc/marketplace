<?php

namespace Tests\Feature\Rfq;

use App\Models\User;
use App\Modules\Rfq\Exceptions\RfqThresholdException;
use App\Modules\Rfq\Services\RfqService;
use App\Modules\Rfq\Services\RfqThresholdService;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfqThresholdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class); // rfq.thresholds_enabled = 0 (off)
    }

    private function buyer(): User
    {
        /** @var User $u */
        $u = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $u->assignRole('customer');
        return $u;
    }

    private function makeRequest(User $buyer): void
    {
        app(RfqService::class)->createRequest($buyer, ['part_description' => 'X', 'location' => 'Harare']);
    }

    public function test_unlimited_requests_when_thresholds_off(): void
    {
        $buyer = $this->buyer();
        for ($i = 0; $i < 5; $i++) {
            $this->makeRequest($buyer);
        }

        $this->assertTrue(app(RfqThresholdService::class)->withinFreeQuota($buyer));
        $this->assertSame(5, app(RfqThresholdService::class)->monthlyCount($buyer));
    }

    public function test_free_quota_enforced_when_enabled(): void
    {
        app(SettingsService::class)->set('rfq.thresholds_enabled', true); // free quota 3
        $buyer = $this->buyer();

        $this->makeRequest($buyer);
        $this->makeRequest($buyer);
        $this->makeRequest($buyer);

        $this->expectException(RfqThresholdException::class);
        $this->makeRequest($buyer); // 4th
    }

    public function test_quota_block_surfaces_to_buyer_over_http(): void
    {
        app(SettingsService::class)->set('rfq.thresholds_enabled', true);
        $buyer = $this->buyer();
        $this->makeRequest($buyer);
        $this->makeRequest($buyer);
        $this->makeRequest($buyer);

        $this->actingAs($buyer)->post(route('rfq.store'), ['part_description' => 'Y', 'location' => 'Harare'])
            ->assertSessionHasErrors('rfq');
    }

    public function test_deposit_required_only_when_enabled_and_high_value(): void
    {
        $thresholds = app(RfqThresholdService::class);

        // Off → never required.
        $this->assertFalse($thresholds->requiresDeposit(1000.0));

        app(SettingsService::class)->set('rfq.thresholds_enabled', true); // value_threshold 500
        $this->assertTrue($thresholds->requiresDeposit(1000.0));
        $this->assertFalse($thresholds->requiresDeposit(100.0));
        $this->assertSame(50.0, $thresholds->depositAmount(1000.0)); // 5%
    }
}
