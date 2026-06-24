<?php

namespace Tests\Feature\Vehicles;

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
 * H12: end-to-end QA gate proving every listing type (car / motorbike / boat /
 * trailer) is fully wired — type-scoped body validation, publish, catalogue
 * filtering + counts, and the detail page.
 */
class AllTypesQaGateTest extends TestCase
{
    use RefreshDatabase;

    /** A valid body type per listing type, and a body type that belongs to a *different* type. */
    private const VALID_BODY = [
        'vehicle'   => 'sedan',
        'motorbike' => 'cruiser',
        'boat'      => 'fishing',
        'trailer'   => 'flatbed',
    ];
    private const FOREIGN_BODY = [
        'vehicle'   => 'cruiser',  // motorbike
        'motorbike' => 'sedan',    // car
        'boat'      => 'sedan',    // car
        'trailer'   => 'sedan',    // car
    ];

    private VehicleMake $make;
    private VehicleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->make = VehicleMake::create(['name' => 'Generic', 'slug' => 'generic-' . Str::random(4), 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Model', 'slug' => 'model-' . Str::random(4)]);
    }

    private function seller(): User
    {
        $u = User::factory()->create(['role' => 'private_seller', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('private_seller');

        return $u;
    }

    private function payload(string $type, string $bodyType): array
    {
        return [
            'vehicle_type' => $type,
            'make_id'      => $this->make->id,
            'model_id'     => $this->model->id,
            'year'         => 2021,
            'body_type'    => $bodyType,
            'transmission' => 'manual',
            'fuel_type'    => 'petrol',
            'mileage'      => 1000,
            'color'        => 'black',
            'condition'    => 'used',
            'price_usd'    => 5000,
            'action'       => 'publish',
        ];
    }

    private function activeVehicle(string $type, string $bodyType): Vehicle
    {
        return Vehicle::create([
            'user_id' => $this->seller()->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2021, 'body_type' => $bodyType, 'transmission' => 'manual', 'fuel_type' => 'petrol',
            'mileage' => 1000, 'color' => 'black', 'condition' => 'used', 'price_usd' => 5000,
            'vehicle_type' => $type, 'status' => 'active', 'expires_at' => now()->addDays(10),
        ]);
    }

    public function test_every_type_publishes_with_its_own_body_type(): void
    {
        foreach (self::VALID_BODY as $type => $body) {
            $seller = $this->seller();

            $this->actingAs($seller)
                ->post(route('seller.vehicles.store'), $this->payload($type, $body))
                ->assertSessionHasNoErrors()
                ->assertRedirect();

            $this->assertDatabaseHas('vehicles', [
                'user_id' => $seller->id, 'vehicle_type' => $type, 'body_type' => $body, 'status' => 'pending',
            ]);
        }
    }

    public function test_body_type_from_a_different_type_is_rejected(): void
    {
        foreach (self::FOREIGN_BODY as $type => $foreignBody) {
            $this->actingAs($this->seller())
                ->post(route('seller.vehicles.store'), $this->payload($type, $foreignBody))
                ->assertSessionHasErrors('body_type');
        }
    }

    public function test_catalogue_filters_and_counts_each_type(): void
    {
        foreach (self::VALID_BODY as $type => $body) {
            $this->activeVehicle($type, $body);
        }

        $counts = app(VehicleRepositoryInterface::class)->countByType();
        foreach (array_keys(self::VALID_BODY) as $type) {
            $this->assertSame(1, $counts[$type] ?? 0, "count for {$type}");
        }

        // Each type tab returns only its own listing.
        foreach (array_keys(self::VALID_BODY) as $type) {
            $ids = app(VehicleRepositoryInterface::class)->paginatePublic(['vehicle_type' => $type])->pluck('vehicle_type')->unique();
            $this->assertSame([$type], $ids->values()->all());
        }
    }

    public function test_detail_page_renders_for_each_type(): void
    {
        foreach (self::VALID_BODY as $type => $body) {
            $vehicle = $this->activeVehicle($type, $body);

            $this->get(route('vehicles.show', $vehicle))
                ->assertOk()
                ->assertSee($vehicle->displayTitle());
        }
    }

    public function test_catalogue_index_renders_type_tabs_for_all_four(): void
    {
        foreach (self::VALID_BODY as $type => $body) {
            $this->activeVehicle($type, $body);
        }

        $res = $this->get(route('vehicles.index'))->assertOk();
        foreach (config('vehicle_types.types') as $cfg) {
            $res->assertSee($cfg['plural']);
        }
    }
}
