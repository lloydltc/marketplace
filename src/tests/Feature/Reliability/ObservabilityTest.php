<?php

namespace Tests\Feature\Reliability;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P6: reliability & observability — health endpoint, branded error pages, and a
 * correlation id on every response.
 */
class ObservabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_health_endpoint_reports_ok_with_dependency_checks(): void
    {
        $this->getJson(route('health'))
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', 'ok')
            ->assertJsonPath('checks.cache', 'ok')
            ->assertJsonPath('checks.storage', 'ok');
    }

    public function test_every_response_carries_a_correlation_id(): void
    {
        $res = $this->get(route('home'))->assertOk();
        $this->assertNotEmpty($res->headers->get('X-Request-Id'));
    }

    public function test_incoming_request_id_is_preserved(): void
    {
        $res = $this->withHeaders(['X-Request-Id' => 'trace-abc-123'])
            ->get(route('home'))->assertOk();

        $this->assertSame('trace-abc-123', $res->headers->get('X-Request-Id'));
    }

    public function test_branded_404_page_is_rendered(): void
    {
        $res = $this->get('/this-route-does-not-exist-xyz')->assertNotFound();
        $res->assertSee('Page not found');
        $res->assertSee('SalmaDrive'); // branded shell, not a raw stack trace
    }

    public function test_branded_403_page_is_rendered(): void
    {
        // A non-customer hitting a buyer-only surface → 403 with the branded page.
        $seller = User::factory()->create([
            'role' => 'private_seller', 'status' => 'active',
            'email_verified_at' => now(), 'force_password_change' => false,
        ]);
        $seller->assignRole('private_seller');

        $this->actingAs($seller)->get(route('cart.index'))
            ->assertForbidden()
            ->assertSee('Access Denied');
    }
}
