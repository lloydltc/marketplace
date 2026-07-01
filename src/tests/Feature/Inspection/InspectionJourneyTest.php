<?php

namespace Tests\Feature\Inspection;

use App\Models\User;
use App\Modules\Inspection\Models\Inspection;
use App\Modules\Inspection\Models\Inspector;
use App\Modules\Inspection\Services\InspectionBookingService;
use App\Modules\Settings\Services\SettingsService;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use App\Notifications\InspectionReportReadyNotification;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * TI3/TI4/TI5: inspection booking (fee from config), inspector report + buyer
 * rating, admin panel — the manual-first inspection marketplace end-to-end.
 */
class InspectionJourneyTest extends TestCase
{
    use RefreshDatabase;

    private VehicleMake $make;
    private VehicleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        // Free fee → skip external gateway (instant paid), exercising the full flow.
        app(SettingsService::class)->set('inspection.fee_usd', '0');
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
        ]);
    }

    private function buyer(): User
    {
        $u = User::factory()->create(['role' => 'customer', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('customer');

        return $u;
    }

    private function admin(): User
    {
        $u = User::factory()->create(['role' => 'admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('admin');

        return $u;
    }

    public function test_admin_can_add_inspector_to_panel(): void
    {
        $inspectorUser = User::factory()->create(['role' => 'customer', 'status' => 'active', 'email' => 'insp@x.com']);

        $this->actingAs($this->admin())->post(route('admin.inspectors.store'), [
            'name' => 'Harare Auto Checks', 'kind' => 'company', 'coverage_area' => 'Harare', 'link_email' => 'insp@x.com',
        ])->assertRedirect();

        $this->assertDatabaseHas('inspectors', ['name' => 'Harare Auto Checks', 'user_id' => $inspectorUser->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'inspector.create']);
    }

    public function test_buyer_books_and_pays_then_inspector_reports_then_buyer_rates(): void
    {
        Notification::fake();
        $inspectorUser = User::factory()->create(['role' => 'customer', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $inspectorUser->assignRole('customer');
        $inspector = Inspector::create(['name' => 'Ace Mechanics', 'kind' => 'mechanic', 'user_id' => $inspectorUser->id, 'is_active' => true]);
        $buyer = $this->buyer();
        $vehicle = $this->vehicle();

        // Book (free fee → instant paid).
        $this->actingAs($buyer)->post(route('inspections.store', $vehicle), ['inspector_id' => $inspector->id])->assertRedirect();
        $inspection = Inspection::where('buyer_id', $buyer->id)->firstOrFail();
        $this->assertSame('paid', $inspection->status);

        // Inspector submits the standardized report.
        $this->actingAs($inspectorUser)->post(route('inspector.report', $inspection), [
            'verdict' => 'pass_with_advisories',
            'items'   => ['Engine' => 'pass', 'Brakes' => 'fail'],
            'notes'   => ['Brakes' => 'Front pads worn'],
        ])->assertRedirect();

        $inspection->refresh();
        $this->assertSame('completed', $inspection->status);
        $this->assertSame('pass_with_advisories', $inspection->verdict);
        Notification::assertSentTo($buyer, InspectionReportReadyNotification::class);

        // Buyer views the report + rates the inspector.
        $this->actingAs($buyer)->get(route('inspections.show', $inspection))->assertOk()->assertSee('Front pads worn');
        $this->actingAs($buyer)->post(route('inspections.rate', $inspection), ['rating' => 5])->assertRedirect();

        $this->assertSame(5, $inspection->fresh()->rating_given);
        $this->assertEquals(5.0, (float) $inspector->fresh()->rating);
        $this->assertSame(1, $inspector->fresh()->review_count);
    }

    public function test_inspector_cannot_report_on_another_inspectors_job(): void
    {
        $u1 = User::factory()->create(['role' => 'customer', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u1->assignRole('customer');
        $other = Inspector::create(['name' => 'Other', 'kind' => 'mechanic', 'user_id' => $u1->id, 'is_active' => true]);
        $assigned = Inspector::create(['name' => 'Assigned', 'kind' => 'mechanic', 'is_active' => true]);
        $buyer = $this->buyer();
        $inspection = app(InspectionBookingService::class)->book($buyer, $assigned, $this->vehicle(), null, null);
        app(InspectionBookingService::class)->markPaid($inspection, 'test');

        $this->actingAs($u1)->post(route('inspector.report', $inspection), ['verdict' => 'pass', 'items' => []])->assertForbidden();
    }

    public function test_buyer_cannot_rate_before_report(): void
    {
        $inspector = Inspector::create(['name' => 'Ace', 'kind' => 'mechanic', 'is_active' => true]);
        $buyer = $this->buyer();
        $inspection = app(InspectionBookingService::class)->book($buyer, $inspector, $this->vehicle(), null, null);
        app(InspectionBookingService::class)->markPaid($inspection, 'test');

        $this->actingAs($buyer)->post(route('inspections.rate', $inspection), ['rating' => 5])->assertStatus(422);
    }

    public function test_fee_comes_from_platform_settings(): void
    {
        app(SettingsService::class)->set('inspection.fee_usd', '45');

        $this->assertSame(4500, app(InspectionBookingService::class)->feeMinor());
    }
}
