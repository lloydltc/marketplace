<?php

namespace Tests\Feature\Engagement;

use App\Models\User;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use App\Support\CompareList;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * AC3: comparison up to 5, fuel/ownership-cost rows, printable, shareable URL.
 */
class ComparisonAC3Test extends TestCase
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
            'year' => 2018, 'body_type' => 'pickup', 'transmission' => 'manual', 'fuel_type' => 'diesel',
            'mileage' => 1000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 20000,
            'vehicle_type' => 'vehicle', 'status' => 'active', 'expires_at' => now()->addDays(10),
        ], $attrs));
    }

    public function test_compare_allows_up_to_five(): void
    {
        $compare = app(CompareList::class);
        $vehicles = collect(range(1, 6))->map(fn () => $this->vehicle());

        foreach ($vehicles as $v) {
            $compare->add($v->id);
        }

        $this->assertSame(5, $compare->count()); // capped at 5
    }

    public function test_compare_page_shows_diff_and_cost_rows(): void
    {
        $a = $this->vehicle(['year' => 2018, 'fuel_type' => 'diesel']);
        $b = $this->vehicle(['year' => 2021, 'fuel_type' => 'petrol']);
        app(CompareList::class)->add($a->id);
        app(CompareList::class)->add($b->id);

        $this->get(route('compare.show'))
            ->assertOk()
            ->assertSee('Est. 5-yr fuel')   // deterministic ownership/fuel cost row
            ->assertSee('Engine')
            ->assertSee('Print')
            ->assertSee('Share');
    }

    public function test_shareable_url_loads_a_compare_set(): void
    {
        $a = $this->vehicle(['year' => 2014]);
        $b = $this->vehicle(['year' => 2016]);

        // No session set — the ?v= ids drive the comparison.
        $this->get(route('compare.show', ['v' => $a->id . ',' . $b->id]))
            ->assertOk()
            ->assertSee('2014 Toyota Hilux')
            ->assertSee('2016 Toyota Hilux');
    }
}
