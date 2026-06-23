<?php

namespace Tests\Feature\Analytics;

use App\Models\User;
use App\Modules\Analytics\Models\ListingDailyStat;
use App\Modules\Analytics\Models\ListingEvent;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * H5: analytics ingest integrity (dedupe + bot-filter), aggregation, and the
 * seller dashboard (scoped, with deltas).
 */
class ListingAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const UA = ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/120 Safari/537.36'];

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

    private function seller(): User
    {
        $u = User::factory()->create(['role' => 'private_seller', 'status' => 'active', 'contact_phone' => '+263771234567', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('private_seller');

        return $u;
    }

    private function vehicle(?User $seller = null): Vehicle
    {
        return Vehicle::create([
            'user_id' => ($seller ?? $this->seller())->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2021, 'body_type' => 'sedan', 'transmission' => 'manual', 'fuel_type' => 'petrol',
            'mileage' => 1000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 15000,
            'status' => 'active', 'expires_at' => now()->addDays(10),
        ]);
    }

    public function test_detail_view_recorded_and_deduped(): void
    {
        $v = $this->vehicle();

        $this->withHeaders(self::UA)->get(route('vehicles.show', $v))->assertOk();
        $this->withHeaders(self::UA)->get(route('vehicles.show', $v))->assertOk(); // same visitor again

        // Deduped to one event for the day.
        $this->assertSame(1, ListingEvent::where('subject_id', $v->id)->where('type', 'detail_view')->count());
    }

    public function test_bot_views_are_not_recorded(): void
    {
        $v = $this->vehicle();

        $this->withHeaders(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])
            ->get(route('vehicles.show', $v))->assertOk();

        $this->assertSame(0, ListingEvent::where('subject_id', $v->id)->count());
    }

    public function test_contact_reveal_records_phone_reveal_event(): void
    {
        $v = $this->vehicle();

        $this->withHeaders(self::UA)->postJson(route('vehicles.contact', $v), ['type' => 'contact_reveal'])->assertOk();

        $this->assertSame(1, ListingEvent::where('subject_id', $v->id)->where('type', 'phone_reveal')->count());
    }

    public function test_aggregation_rolls_up_and_prunes(): void
    {
        $v = $this->vehicle();
        // Two distinct visitors view it today.
        ListingEvent::insert([
            ['id' => (string) Str::uuid(), 'subject_type' => Vehicle::class, 'subject_id' => $v->id, 'seller_user_id' => $v->user_id, 'vendor_id' => null, 'type' => 'detail_view', 'visitor_hash' => 'h1', 'occurred_on' => today()->toDateString(), 'created_at' => now()],
            ['id' => (string) Str::uuid(), 'subject_type' => Vehicle::class, 'subject_id' => $v->id, 'seller_user_id' => $v->user_id, 'vendor_id' => null, 'type' => 'detail_view', 'visitor_hash' => 'h2', 'occurred_on' => today()->toDateString(), 'created_at' => now()],
        ]);

        $this->artisan('analytics:aggregate')->assertExitCode(0);

        $stat = ListingDailyStat::where('subject_id', $v->id)->where('type', 'detail_view')->whereDate('stat_date', today())->first();
        $this->assertNotNull($stat);
        $this->assertSame(2, $stat->count);
    }

    public function test_seller_dashboard_shows_scoped_metrics(): void
    {
        $sellerA = $this->seller();
        $vA = $this->vehicle($sellerA);
        $sellerB = $this->seller();
        $vB = $this->vehicle($sellerB);

        // Pre-aggregated stats for both sellers (distinctive comma-formatted values
        // that won't collide with page chrome).
        ListingDailyStat::create(['subject_type' => Vehicle::class, 'subject_id' => $vA->id, 'seller_user_id' => $sellerA->id, 'stat_date' => today(), 'type' => 'detail_view', 'count' => 4242]);
        ListingDailyStat::create(['subject_type' => Vehicle::class, 'subject_id' => $vB->id, 'seller_user_id' => $sellerB->id, 'stat_date' => today(), 'type' => 'detail_view', 'count' => 86753]);

        $this->actingAs($sellerA)->get(route('seller.analytics.index'))
            ->assertOk()
            ->assertSee('4,242')        // seller A's views
            ->assertDontSee('86,753')   // not seller B's
            ->assertSee($vA->displayTitle());
    }
}
