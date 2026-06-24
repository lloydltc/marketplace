<?php

namespace Tests\Feature\Engagement;

use App\Models\User;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use App\Modules\Vehicles\Repositories\VehicleRepositoryInterface;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * H7: compare set, recently-viewed rail, and sponsored rows.
 */
class EngagementTest extends TestCase
{
    use RefreshDatabase;

    private VehicleMake $make;
    private VehicleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->make = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
    }

    private function vehicle(array $attrs = []): Vehicle
    {
        $seller = User::factory()->create(['role' => 'private_seller', 'status' => 'active']);

        return Vehicle::create(array_merge([
            'user_id' => $seller->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2021, 'body_type' => 'sedan', 'transmission' => 'manual', 'fuel_type' => 'petrol',
            'mileage' => 1000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 15000,
            'vehicle_type' => 'vehicle', 'status' => 'active', 'expires_at' => now()->addDays(10),
        ], $attrs));
    }

    // ─── Compare ────────────────────────────────────────────────────────────────

    public function test_buyer_can_add_and_view_compared_vehicles(): void
    {
        $a = $this->vehicle(['year' => 2020]);
        $b = $this->vehicle(['year' => 2023]);

        $this->post(route('compare.add', $a))->assertRedirect();
        $this->post(route('compare.add', $b))->assertRedirect();

        $this->get(route('compare.show'))
            ->assertOk()
            ->assertSee('2020 Toyota Hilux')
            ->assertSee('2023 Toyota Hilux');
    }

    public function test_compare_set_is_capped(): void
    {
        config(['engagement.compare.max_items' => 1]);
        $a = $this->vehicle(['year' => 2020]);
        $b = $this->vehicle(['year' => 2023]);

        $this->post(route('compare.add', $a));
        $this->post(route('compare.add', $b)); // over the cap — rejected

        $this->get(route('compare.show'))
            ->assertOk()
            ->assertSee('2020 Toyota Hilux')
            ->assertDontSee('2023 Toyota Hilux');
    }

    public function test_buyer_can_remove_from_compare(): void
    {
        $a = $this->vehicle(['year' => 2020]);
        $this->post(route('compare.add', $a));

        $this->delete(route('compare.remove', $a))->assertRedirect();

        $this->get(route('compare.show'))
            ->assertOk()
            ->assertSee('No vehicles to compare');
    }

    public function test_compare_bar_reflects_count_across_pages(): void
    {
        $a = $this->vehicle();
        $this->post(route('compare.add', $a));

        $this->get(route('vehicles.index'))
            ->assertOk()
            ->assertSee('to compare');
    }

    // ─── Recently viewed ──────────────────────────────────────────────────────

    public function test_viewing_a_listing_sets_the_recently_viewed_cookie(): void
    {
        $v = $this->vehicle();

        $this->get(route('vehicles.show', $v))
            ->assertOk()
            ->assertCookie('recently_viewed_vehicles');
    }

    public function test_home_renders_recently_viewed_from_cookie(): void
    {
        $v = $this->vehicle(['year' => 2019]);

        // recently_viewed is a plain (unencrypted) cookie of public UUIDs.
        $this->withUnencryptedCookie('recently_viewed_vehicles', $v->id)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Recently viewed')
            ->assertSee('2019 Toyota Hilux');
    }

    // ─── Sponsored ──────────────────────────────────────────────────────────────

    public function test_sponsored_returns_only_featured_active_listings(): void
    {
        $featured = $this->vehicle(['featured_until' => now()->addDays(5)]);
        $plain    = $this->vehicle();

        $sponsored = app(VehicleRepositoryInterface::class)->sponsored(10);

        $this->assertTrue($sponsored->contains('id', $featured->id));
        $this->assertFalse($sponsored->contains('id', $plain->id));
    }

    public function test_home_renders_sponsored_row(): void
    {
        $this->vehicle(['featured_until' => now()->addDays(5), 'year' => 2018]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Sponsored listings')
            ->assertSee('2018 Toyota Hilux');
    }
}
