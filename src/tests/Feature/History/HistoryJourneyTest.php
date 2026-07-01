<?php

namespace Tests\Feature\History;

use App\Models\User;
use App\Modules\History\Models\HistoryReport;
use App\Modules\Settings\Services\SettingsService;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\HistoryDataSourceSeeder;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * HR5: end-to-end gate — assemble from live sources → preview → purchase →
 * render full; unavailable sources honest; pricing from config; actions audited.
 */
class HistoryJourneyTest extends TestCase
{
    use RefreshDatabase;

    private VehicleMake $make;
    private VehicleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->seed(HistoryDataSourceSeeder::class);
        app(SettingsService::class)->set('history.report_price_usd', '0'); // free path → no external gateway
        $this->make = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
    }

    public function test_full_history_journey(): void
    {
        $seller = User::factory()->create(['role' => 'private_seller', 'status' => 'active']);
        $vehicle = Vehicle::create([
            'user_id' => $seller->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2018, 'body_type' => 'pickup', 'transmission' => 'manual', 'fuel_type' => 'diesel',
            'mileage' => 85000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 20000,
            'vehicle_type' => 'vehicle', 'status' => 'active', 'expires_at' => now()->addDays(10),
            'vin' => strtoupper(Str::random(17)), 'is_recent_import' => true, 'duty_paid' => true,
        ]);

        $buyer = User::factory()->create(['role' => 'customer', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $buyer->assignRole('customer');

        // Preview (public) — available + honest unavailable sections.
        $this->get(route('history.preview', $vehicle))->assertOk()->assertSee('Import record')->assertSee('Not available');

        // Purchase (free → instant) and render the full report.
        $this->actingAs($buyer)->post(route('history.purchase', $vehicle))->assertRedirect();
        $report = HistoryReport::where('requested_by', $buyer->id)->firstOrFail();
        $this->assertSame('purchased', $report->status);

        $this->actingAs($buyer)->get(route('history.show', $report))
            ->assertOk()
            ->assertSee('Ownership & listing history')
            ->assertSee('Registration / ZINARA')   // gated source still listed
            ->assertSee('Not available');           // ...honestly

        // Audited.
        $this->assertDatabaseHas('audit_logs', ['action' => 'history.report.purchased', 'target_id' => $report->id]);
    }
}
