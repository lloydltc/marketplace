<?php

namespace Tests\Feature\Navigation;

use App\Models\User;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P8: mobile users must have working navigation (the old nav only hid links on
 * small screens with no menu). Assert the hamburger toggle + drawer render.
 */
class MobileNavTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    public function test_mobile_menu_toggle_is_present(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('aria-label="Toggle navigation menu"', false)
            ->assertSee('mobileOpen', false); // Alpine drawer state
    }

    public function test_seller_role_link_appears_in_mobile_drawer(): void
    {
        $seller = User::factory()->create([
            'role' => 'private_seller', 'status' => 'active',
            'email_verified_at' => now(), 'force_password_change' => false,
        ]);
        $seller->assignRole('private_seller');

        // "Sales" should now appear in BOTH the desktop row and the mobile drawer.
        $html = $this->actingAs($seller)->get(route('home'))->assertOk()->getContent();
        $this->assertGreaterThanOrEqual(2, substr_count($html, '>Sales<'));
    }
}
