<?php

namespace Tests\Feature\History;

use App\Models\User;
use App\Modules\History\Models\HistoryReport;
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
 * HR3: preview (free, public) → purchase → full view (owner-only, purchased) →
 * purchased list. Honest "not available" sections; locked sections in preview.
 */
class ReportPurchaseTest extends TestCase
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
        // Free reports so the gateway is skipped (instant grant) — keeps the test
        // off the external sandbox while exercising the full purchase→view flow.
        // (The seeded platform setting overrides config, so set it via the service.)
        app(\App\Modules\Settings\Services\SettingsService::class)->set('history.report_price_usd', '0');
        $this->make = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
    }

    private function vehicle(): Vehicle
    {
        $seller = User::factory()->create(['role' => 'private_seller', 'status' => 'active']);

        return Vehicle::create([
            'user_id' => $seller->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2018, 'body_type' => 'pickup', 'transmission' => 'manual', 'fuel_type' => 'diesel',
            'mileage' => 85000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 20000,
            'vehicle_type' => 'vehicle', 'status' => 'active', 'expires_at' => now()->addDays(10),
            'vin' => strtoupper(Str::random(17)), 'is_recent_import' => true,
        ]);
    }

    private function buyer(): User
    {
        $u = User::factory()->create(['role' => 'customer', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('customer');

        return $u;
    }

    public function test_preview_is_public_and_shows_locked_and_unavailable_states(): void
    {
        $vehicle = $this->vehicle();

        $this->get(route('history.preview', $vehicle))
            ->assertOk()
            ->assertSee('Vehicle history report')
            ->assertSee('Import record')                       // preview section
            ->assertSee('Purchase the full report to view')    // a locked section
            ->assertSee('Not available');                      // a gated source, honest
    }

    public function test_vehicle_detail_links_to_history(): void
    {
        $vehicle = $this->vehicle();

        $this->get(route('vehicles.show', $vehicle))
            ->assertOk()
            ->assertSee('Vehicle history available');
    }

    public function test_purchase_then_owner_can_view_full_report(): void
    {
        $buyer = $this->buyer();
        $vehicle = $this->vehicle();

        $this->actingAs($buyer)->post(route('history.purchase', $vehicle))->assertRedirect();

        $report = HistoryReport::where('requested_by', $buyer->id)->first();
        $this->assertSame('purchased', $report->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'history.report.purchased', 'target_id' => $report->id]);

        $this->actingAs($buyer)->get(route('history.show', $report))
            ->assertOk()
            ->assertSee('Download / print PDF')
            ->assertSee('Ownership & listing history'); // full section now visible
    }

    public function test_non_owner_cannot_view_report(): void
    {
        $buyer = $this->buyer();
        $vehicle = $this->vehicle();
        $this->actingAs($buyer)->post(route('history.purchase', $vehicle));
        $report = HistoryReport::where('requested_by', $buyer->id)->first();

        $intruder = $this->buyer();
        $this->actingAs($intruder)->get(route('history.show', $report))->assertForbidden();
    }

    public function test_unpurchased_report_is_payment_required(): void
    {
        app(\App\Modules\Settings\Services\SettingsService::class)->set('history.report_price_usd', '5'); // non-free → not auto-purchased
        $buyer = $this->buyer();
        $vehicle = $this->vehicle();
        $report = app(\App\Modules\History\Services\ReportAssembler::class)->assembleFor($vehicle, $buyer->id);

        $this->actingAs($buyer)->get(route('history.show', $report))->assertStatus(402);
    }

    public function test_purchased_reports_list(): void
    {
        $buyer = $this->buyer();
        $vehicle = $this->vehicle();
        $this->actingAs($buyer)->post(route('history.purchase', $vehicle));

        $this->actingAs($buyer)->get(route('history.index'))
            ->assertOk()
            ->assertSee('2018 Toyota Hilux');
    }
}
