<?php

namespace Tests\Feature\History;

use App\Models\User;
use App\Modules\History\Models\HistoryDataSource;
use App\Modules\History\Services\HistoryPurchaseService;
use App\Modules\History\Services\ReportAssembler;
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
 * HR4: admin source management, manual service entry, refunds — all audited.
 */
class AdminHistoryTest extends TestCase
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
        $this->make = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
    }

    private function admin(): User
    {
        $u = User::factory()->create(['role' => 'admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('admin');

        return $u;
    }

    private function vehicle(): Vehicle
    {
        $seller = User::factory()->create(['role' => 'private_seller', 'status' => 'active']);

        return Vehicle::create([
            'user_id' => $seller->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2018, 'body_type' => 'pickup', 'transmission' => 'manual', 'fuel_type' => 'diesel',
            'mileage' => 85000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 20000,
            'vehicle_type' => 'vehicle', 'status' => 'active', 'expires_at' => now()->addDays(10),
        ]);
    }

    public function test_admin_updates_source_status_audited(): void
    {
        $source = HistoryDataSource::where('key', 'registration')->first();

        $this->actingAs($this->admin())
            ->post(route('admin.history.sources.update', $source), ['status' => 'live'])
            ->assertRedirect();

        $this->assertSame('live', $source->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'history.source.update', 'target_id' => $source->id]);
    }

    public function test_admin_adds_manual_service_record(): void
    {
        $buyer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $report = app(ReportAssembler::class)->assembleFor($this->vehicle(), $buyer->id);

        $this->actingAs($this->admin())
            ->post(route('admin.history.service-records.add', $report), ['date' => '2023-05-01', 'note' => 'Cambelt replaced', 'odometer_km' => 70000])
            ->assertRedirect();

        $section = $report->sections()->where('type', 'service')->first();
        $this->assertSame('Cambelt replaced', $section->data['records'][0]['note']);
        $this->assertSame('manual', $section->availability);
        $this->assertDatabaseHas('audit_logs', ['action' => 'history.service_record.add', 'target_id' => $report->id]);
    }

    public function test_admin_refunds_a_purchased_report_audited(): void
    {
        $buyer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $report = app(ReportAssembler::class)->assembleFor($this->vehicle(), $buyer->id);
        app(HistoryPurchaseService::class)->markPurchased($report, 'test');

        $this->actingAs($this->admin())->post(route('admin.history.refund', $report))->assertRedirect();

        $this->assertSame('refunded', $report->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'history.report.refunded', 'target_id' => $report->id]);
    }

    public function test_non_admin_cannot_manage_history(): void
    {
        $seller = User::factory()->create(['role' => 'private_seller', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $seller->assignRole('private_seller');

        $this->actingAs($seller)->get(route('admin.history.index'))->assertForbidden();
    }
}
