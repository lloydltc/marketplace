<?php

namespace Tests\Feature\Engagement;

use App\Models\SavedSearch;
use App\Models\User;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use App\Notifications\SavedSearchAlertNotification;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * H7: saved-search email alerts. Buyers are told about new matching listings
 * once, never re-notified about the same listing, and only when opted in.
 */
class SavedSearchAlertTest extends TestCase
{
    use RefreshDatabase;

    private VehicleMake $make;
    private VehicleModel $model;
    private User $buyer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->make = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
        $this->buyer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
    }

    private function activeVehicle(array $attrs = []): Vehicle
    {
        $seller = User::factory()->create(['role' => 'private_seller', 'status' => 'active']);

        return Vehicle::create(array_merge([
            'user_id' => $seller->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2021, 'body_type' => 'sedan', 'transmission' => 'manual', 'fuel_type' => 'petrol',
            'mileage' => 1000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 15000,
            'vehicle_type' => 'vehicle', 'status' => 'active', 'published_at' => now(), 'expires_at' => now()->addDays(10),
        ], $attrs));
    }

    private function savedSearch(bool $notify, array $params = []): SavedSearch
    {
        return SavedSearch::create([
            'user_id' => $this->buyer->id, 'name' => 'Toyotas', 'type' => 'vehicles',
            'query_params' => array_merge(['make_id' => $this->make->id], $params), 'notify' => $notify,
        ]);
    }

    public function test_alerts_notify_about_new_matches_and_set_watermark(): void
    {
        Notification::fake();
        $search = $this->savedSearch(notify: true);
        $this->activeVehicle();

        $this->artisan('alerts:saved-searches')->assertExitCode(0);

        Notification::assertSentTo($this->buyer, SavedSearchAlertNotification::class);
        $this->assertNotNull($search->fresh()->last_notified_at);
    }

    public function test_alerts_do_not_repeat_for_the_same_listing(): void
    {
        Notification::fake();
        $this->savedSearch(notify: true);
        $this->activeVehicle();

        $this->artisan('alerts:saved-searches'); // first digest
        Notification::assertSentToTimes($this->buyer, SavedSearchAlertNotification::class, 1);

        $this->artisan('alerts:saved-searches'); // nothing new → no second email
        Notification::assertSentToTimes($this->buyer, SavedSearchAlertNotification::class, 1);
    }

    public function test_new_listing_after_watermark_triggers_a_fresh_alert(): void
    {
        Notification::fake();
        $this->savedSearch(notify: true);
        $this->activeVehicle();

        $this->artisan('alerts:saved-searches');
        Notification::assertSentToTimes($this->buyer, SavedSearchAlertNotification::class, 1);

        // A listing that appears after the first digest must be alerted next run.
        $this->travel(1)->hours();
        $this->activeVehicle(['published_at' => now()]);
        $this->artisan('alerts:saved-searches');
        Notification::assertSentToTimes($this->buyer, SavedSearchAlertNotification::class, 2);
    }

    public function test_searches_without_alerts_are_never_notified(): void
    {
        Notification::fake();
        $this->savedSearch(notify: false);
        $this->activeVehicle();

        $this->artisan('alerts:saved-searches');

        Notification::assertNothingSent();
    }

    public function test_alerts_respect_the_saved_filters(): void
    {
        Notification::fake();
        // Buyer only wants SUVs; a sedan is published.
        $this->savedSearch(notify: true, params: ['body_type' => 'suv']);
        $this->activeVehicle(['body_type' => 'sedan']);

        $this->artisan('alerts:saved-searches');

        Notification::assertNothingSent();
    }
}
