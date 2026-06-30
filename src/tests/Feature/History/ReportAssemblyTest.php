<?php

namespace Tests\Feature\History;

use App\Models\User;
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
 * HR1/HR2: report schema + staged adapters + assembler with honest provenance,
 * confidence, and "unavailable" states (no fabrication).
 */
class ReportAssemblyTest extends TestCase
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

    private function vehicle(array $attrs = []): Vehicle
    {
        $seller = User::factory()->create(['role' => 'private_seller', 'status' => 'active']);

        return Vehicle::create(array_merge([
            'user_id' => $seller->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2018, 'body_type' => 'pickup', 'transmission' => 'manual', 'fuel_type' => 'diesel',
            'mileage' => 85000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 20000, 'published_at' => now(),
            'vehicle_type' => 'vehicle', 'status' => 'active', 'expires_at' => now()->addDays(10),
            'vin' => strtoupper(Str::random(17)), 'is_recent_import' => true, 'duty_paid' => true, 'steering' => 'rhd',
        ], $attrs));
    }

    public function test_data_sources_seeded_with_honest_status(): void
    {
        $this->assertDatabaseHas('history_data_sources', ['key' => 'import', 'status' => 'live']);
        $this->assertDatabaseHas('history_data_sources', ['key' => 'service', 'status' => 'manual']);
        $this->assertDatabaseHas('history_data_sources', ['key' => 'registration', 'status' => 'unavailable']);
    }

    public function test_assembler_builds_sections_with_provenance_and_price(): void
    {
        $vehicle = $this->vehicle();
        $report = app(ReportAssembler::class)->assembleFor($vehicle);

        // Price snapshotted from platform settings (5.00 → 500 minor).
        $this->assertSame(500, $report->price_minor);

        $import = $report->sections->firstWhere('source', 'import');
        $this->assertSame('available', $import->availability);
        $this->assertSame('Seller-declared import details', $import->provenance);
        $this->assertNotNull($import->retrieved_at);

        $platform = $report->sections->firstWhere('source', 'platform');
        $this->assertSame('high', $platform->confidence);
        $this->assertSame('Private seller', $platform->data['owner_type']); // owned by a private seller here
    }

    public function test_gated_sources_render_unavailable_not_fabricated(): void
    {
        $report = app(ReportAssembler::class)->assembleFor($this->vehicle());

        $registration = $report->sections->firstWhere('source', 'registration');
        $this->assertSame('unavailable', $registration->availability);
        $this->assertNull($registration->data);
        $this->assertStringContainsString('pending data partnership', strtolower($registration->provenance));
    }

    public function test_platform_section_includes_price_history(): void
    {
        $vehicle = $this->vehicle(['price_usd' => 20000]);
        $vehicle->update(['price_usd' => 18000]); // recorded by AC2 hook

        $report = app(ReportAssembler::class)->assembleFor($vehicle);
        $platform = $report->sections->firstWhere('source', 'platform');

        $this->assertNotEmpty($platform->data['price_changes']);
        $this->assertTrue($platform->data['price_changes'][0]['is_drop']);
    }

    public function test_reassembly_preserves_manual_service_records(): void
    {
        $vehicle = $this->vehicle();
        $report = app(ReportAssembler::class)->assembleFor($vehicle);

        // Admin enters a service record (HR4 will use this path).
        $report->sections()->where('type', 'service')->update([
            'data' => json_encode(['records' => [['date' => '2023-01-01', 'note' => 'Major service']]]),
        ]);

        // Re-assemble (e.g. new price change) must not wipe the manual records.
        $report = app(ReportAssembler::class)->assembleFor($vehicle);
        $service = $report->sections->firstWhere('source', 'service');

        $this->assertSame('Major service', $service->data['records'][0]['note']);
    }
}
