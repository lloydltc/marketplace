<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Modules\Settings\Models\PlatformSetting;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    private function admin(): User
    {
        // Platform settings are super_admin-only (per the role matrix).
        /** @var User $user */
        $user = User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);
        $user->assignRole('super_admin');
        return $user;
    }

    public function test_service_returns_seeded_defaults_with_correct_types(): void
    {
        $settings = app(SettingsService::class);

        $this->assertSame(10.0, $settings->getDecimal('commission.default_rate'));
        $this->assertSame(3, $settings->getInt('rfq.free_quota_monthly'));
        $this->assertFalse($settings->getBool('cod.vf_enabled'));
        $this->assertTrue($settings->getBool('cod.fbs_enabled'));
        $this->assertSame('weekly', $settings->getString('wallet.payout_cycle'));
    }

    public function test_missing_key_returns_default(): void
    {
        $settings = app(SettingsService::class);

        $this->assertSame(42, $settings->getInt('does.not.exist', 42));
    }

    public function test_set_persists_and_invalidates_cache(): void
    {
        $settings = app(SettingsService::class);

        // Prime the cache.
        $this->assertSame(10.0, $settings->getDecimal('commission.default_rate'));

        $settings->set('commission.default_rate', 12.5);

        $this->assertSame(12.5, $settings->getDecimal('commission.default_rate'));
        $this->assertDatabaseHas('platform_settings', [
            'key'   => 'commission.default_rate',
            'value' => '12.5',
        ]);
    }

    public function test_admin_can_view_settings_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('commission.default_rate');
    }

    public function test_non_admin_cannot_view_settings_page(): void
    {
        /** @var User $customer */
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->get(route('admin.settings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_update_a_setting(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), [
                'settings' => [
                    'commission.default_rate' => '15',
                    'rfq.free_quota_monthly'  => '5',
                ],
            ])
            ->assertRedirect(route('admin.settings.index'));

        $settings = app(SettingsService::class);
        $this->assertSame(15.0, $settings->getDecimal('commission.default_rate'));
        $this->assertSame(5, $settings->getInt('rfq.free_quota_monthly'));
    }

    public function test_admin_can_toggle_a_boolean_setting_on(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), [
                'settings' => [
                    'cod.vf_enabled' => '1',
                ],
            ])
            ->assertRedirect();

        $this->assertTrue(app(SettingsService::class)->getBool('cod.vf_enabled'));
    }

    public function test_update_records_the_editing_admin(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'settings' => ['commission.default_rate' => '11'],
            ]);

        $this->assertSame(
            $admin->id,
            PlatformSetting::where('key', 'commission.default_rate')->first()->updated_by
        );
    }

    public function test_invalid_decimal_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), [
                'settings' => ['commission.default_rate' => 'not-a-number'],
            ])
            ->assertSessionHasErrors('settings.commission.default_rate');
    }
}
