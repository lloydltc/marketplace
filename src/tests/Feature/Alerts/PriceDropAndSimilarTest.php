<?php

namespace Tests\Feature\Alerts;

use App\Models\SavedSearch;
use App\Models\User;
use App\Modules\Notifications\Models\ListingPriceHistory;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use App\Notifications\PriceDropNotification;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * AC2: price-drop capture + alerts to matching saved searches, and deterministic
 * similar vehicles.
 */
class PriceDropAndSimilarTest extends TestCase
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

    private function buyer(): User
    {
        $u = User::factory()->create(['role' => 'customer', 'status' => 'active', 'email_verified_at' => now()]);
        $u->assignRole('customer');

        return $u;
    }

    public function test_price_decrease_is_captured_as_a_drop(): void
    {
        $vehicle = $this->vehicle(['price_usd' => 20000]);

        $vehicle->update(['price_usd' => 18000]);

        $this->assertDatabaseHas('listing_price_history', [
            'subject_id' => $vehicle->id, 'old_price' => 20000, 'new_price' => 18000, 'is_drop' => true,
        ]);
    }

    public function test_price_increase_is_recorded_but_not_a_drop(): void
    {
        $vehicle = $this->vehicle(['price_usd' => 20000]);
        $vehicle->update(['price_usd' => 21000]);

        $this->assertDatabaseHas('listing_price_history', ['subject_id' => $vehicle->id, 'is_drop' => false]);
    }

    public function test_price_drop_alerts_matching_saved_search_owner(): void
    {
        Notification::fake();
        $buyer = $this->buyer();
        SavedSearch::create(['user_id' => $buyer->id, 'name' => 'Toyotas', 'type' => 'vehicles',
            'query_params' => ['make_id' => $this->make->id], 'notify' => true]);

        $vehicle = $this->vehicle(['price_usd' => 20000]);
        $vehicle->update(['price_usd' => 17000]); // drop, still matches make filter

        $this->artisan('alerts:price-drops')->assertExitCode(0);

        Notification::assertSentTo($buyer, PriceDropNotification::class);
    }

    public function test_price_drop_does_not_alert_non_matching_search(): void
    {
        Notification::fake();
        $other = VehicleMake::create(['name' => 'Mazda', 'slug' => 'mazda-' . Str::random(4), 'sort_order' => 1]);
        $buyer = $this->buyer();
        SavedSearch::create(['user_id' => $buyer->id, 'name' => 'Mazdas', 'type' => 'vehicles',
            'query_params' => ['make_id' => $other->id], 'notify' => true]);

        $vehicle = $this->vehicle(['price_usd' => 20000]);
        $vehicle->update(['price_usd' => 17000]);

        $this->artisan('alerts:price-drops');

        Notification::assertNothingSentTo($buyer);
    }

    public function test_price_drop_alerts_are_not_duplicated(): void
    {
        Notification::fake();
        $buyer = $this->buyer();
        SavedSearch::create(['user_id' => $buyer->id, 'name' => 'Toyotas', 'type' => 'vehicles',
            'query_params' => ['make_id' => $this->make->id], 'notify' => true]);
        $vehicle = $this->vehicle(['price_usd' => 20000]);
        $vehicle->update(['price_usd' => 17000]);

        $this->artisan('alerts:price-drops');
        $this->artisan('alerts:price-drops'); // re-run: already alerted

        Notification::assertSentToTimes($buyer, PriceDropNotification::class, 1);
    }

    public function test_similar_vehicles_are_deterministic(): void
    {
        $base = $this->vehicle(['price_usd' => 20000]);
        $similar = $this->vehicle(['price_usd' => 21000]);          // same make + price band
        $tooPricey = $this->vehicle(['price_usd' => 50000]);        // out of band
        $otherType = $this->vehicle(['price_usd' => 20000, 'vehicle_type' => 'motorbike', 'body_type' => 'cruiser']);

        $ids = $base->similar(10)->pluck('id');

        $this->assertTrue($ids->contains($similar->id));
        $this->assertFalse($ids->contains($tooPricey->id));
        $this->assertFalse($ids->contains($otherType->id));
        $this->assertFalse($ids->contains($base->id));
    }
}
