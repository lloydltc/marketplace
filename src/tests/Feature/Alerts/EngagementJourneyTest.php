<?php

namespace Tests\Feature\Alerts;

use App\Models\SavedSearch;
use App\Models\User;
use App\Modules\Notifications\Services\NotificationChannelResolver;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use App\Notifications\PriceDropNotification;
use App\Notifications\SavedSearchAlertNotification;
use App\Support\CompareList;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * AC5: engagement gate — save a search → receive a new-match and a price-drop
 * alert in-app; preferences are honored (no email when off); no duplicate alerts;
 * compare up to 5.
 */
class EngagementJourneyTest extends TestCase
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
            'year' => 2019, 'body_type' => 'pickup', 'transmission' => 'manual', 'fuel_type' => 'diesel',
            'mileage' => 1000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 20000, 'published_at' => now(),
            'vehicle_type' => 'vehicle', 'status' => 'active', 'expires_at' => now()->addDays(10),
        ], $attrs));
    }

    private function buyer(): User
    {
        $u = User::factory()->create(['role' => 'customer', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('customer');

        return $u;
    }

    public function test_saved_search_yields_inapp_new_match_and_price_drop(): void
    {
        $buyer = $this->buyer();
        SavedSearch::create(['user_id' => $buyer->id, 'name' => 'Toyotas', 'type' => 'vehicles',
            'query_params' => ['make_id' => $this->make->id], 'notify' => true]);

        // New match: a fresh listing appears, daily alert runs (real in-app delivery).
        $vehicle = $this->vehicle(['price_usd' => 20000]);
        $this->artisan('alerts:saved-searches');
        $this->assertSame(1, $buyer->notifications()->where('type', SavedSearchAlertNotification::class)->count());

        // Price drop: it gets cheaper, the price-drop alert runs.
        $vehicle->update(['price_usd' => 16000]);
        $this->artisan('alerts:price-drops');
        $this->assertSame(1, $buyer->notifications()->where('type', PriceDropNotification::class)->count());

        // In-app inbox shows both.
        $this->actingAs($buyer)->get(route('notifications.index'))->assertOk()->assertSee('Price drop');
    }

    public function test_channel_preference_is_honored(): void
    {
        $buyer = $this->buyer();
        // Turn OFF in-app for new-match; resolver should drop the database channel.
        app(NotificationChannelResolver::class)->setPreference($buyer, 'alert.new_match', 'in_app', false);

        $channels = app(NotificationChannelResolver::class)->channels($buyer, 'alert.new_match');

        $this->assertNotContains('in_app', $channels);
        $this->assertContains('email', $channels); // email still on by default
    }

    public function test_no_duplicate_alerts_on_rerun(): void
    {
        $buyer = $this->buyer();
        SavedSearch::create(['user_id' => $buyer->id, 'name' => 'Toyotas', 'type' => 'vehicles',
            'query_params' => ['make_id' => $this->make->id], 'notify' => true]);
        $this->vehicle();

        $this->artisan('alerts:saved-searches');
        $this->artisan('alerts:saved-searches'); // high-water mark prevents repeats

        $this->assertSame(1, $buyer->notifications()->where('type', SavedSearchAlertNotification::class)->count());
    }

    public function test_compare_up_to_five_renders(): void
    {
        $compare = app(CompareList::class);
        collect(range(1, 5))->each(fn () => $compare->add($this->vehicle()->id));

        $this->get(route('compare.show'))->assertOk()->assertSee('Est. 5-yr fuel');
        $this->assertSame(5, $compare->count());
    }
}
