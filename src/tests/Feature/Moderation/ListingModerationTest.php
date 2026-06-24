<?php

namespace Tests\Feature\Moderation;

use App\Models\ListingReport;
use App\Models\User;
use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Products\Models\Product;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * H11: report-listing + rule-based auto-flags + admin moderation queue.
 */
class ListingModerationTest extends TestCase
{
    use RefreshDatabase;

    private VehicleMake $make;
    private VehicleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        config(['moderation.auto.banned_keywords' => ['stolen', 'replica']]);
        config(['moderation.auto.min_reasonable_vehicle_usd' => 100]);
        $this->make = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
    }

    private function vehicle(array $attrs = []): Vehicle
    {
        return Vehicle::create(array_merge([
            'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2021, 'body_type' => 'sedan', 'transmission' => 'manual', 'fuel_type' => 'petrol',
            'mileage' => 1000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 15000,
            'vehicle_type' => 'vehicle', 'status' => 'active', 'expires_at' => now()->addDays(10),
            'description' => 'Clean car', 'user_id' => User::factory()->create(['role' => 'private_seller', 'status' => 'active'])->id,
        ], $attrs));
    }

    private function admin(): User
    {
        $u = User::factory()->create(['role' => 'admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('admin');

        return $u;
    }

    // ─── Buyer reporting ────────────────────────────────────────────────────────

    public function test_guest_can_report_a_vehicle(): void
    {
        $v = $this->vehicle();

        $this->post(route('vehicles.report', $v), ['reason' => 'scam', 'note' => 'Seller wants deposit upfront'])
            ->assertRedirect();

        $this->assertDatabaseHas('listing_reports', [
            'reportable_id' => $v->id, 'reason' => 'scam', 'source' => 'user', 'status' => 'open',
        ]);
    }

    public function test_report_reason_must_be_valid(): void
    {
        $v = $this->vehicle();

        $this->post(route('vehicles.report', $v), ['reason' => 'not-a-reason'])
            ->assertSessionHasErrors('reason');
    }

    public function test_duplicate_report_from_same_reporter_is_ignored(): void
    {
        $v = $this->vehicle();

        $this->post(route('vehicles.report', $v), ['reason' => 'scam']);
        $this->post(route('vehicles.report', $v), ['reason' => 'misleading']);

        $this->assertSame(1, ListingReport::where('reportable_id', $v->id)->count());
    }

    // ─── Rule-based auto-flags ──────────────────────────────────────────────────

    public function test_auto_scan_flags_banned_keyword(): void
    {
        $flagged = $this->vehicle(['description' => 'This is a REPLICA badge special']);
        $clean   = $this->vehicle();

        $this->artisan('moderation:scan')->assertExitCode(0);

        $this->assertDatabaseHas('listing_reports', ['reportable_id' => $flagged->id, 'source' => 'auto', 'reason' => 'prohibited']);
        $this->assertSame(0, ListingReport::where('reportable_id', $clean->id)->count());
    }

    public function test_auto_scan_flags_implausible_price(): void
    {
        $cheap = $this->vehicle(['price_usd' => 50]);

        $this->artisan('moderation:scan');

        $this->assertDatabaseHas('listing_reports', ['reportable_id' => $cheap->id, 'source' => 'auto', 'reason' => 'scam']);
    }

    public function test_auto_scan_is_idempotent(): void
    {
        $this->vehicle(['description' => 'stolen parts here']);

        $this->artisan('moderation:scan');
        $this->artisan('moderation:scan'); // re-run must not duplicate

        $this->assertSame(1, ListingReport::where('source', 'auto')->where('reason', 'prohibited')->count());
    }

    // ─── Admin queue ────────────────────────────────────────────────────────────

    public function test_admin_queue_lists_open_reports(): void
    {
        $v = $this->vehicle();
        $v->reports()->create(['source' => 'user', 'reason' => 'scam', 'status' => 'open']);

        $this->actingAs($this->admin())->get(route('admin.moderation.index'))
            ->assertOk()
            ->assertSee($v->displayTitle())
            ->assertSee('Looks like a scam');
    }

    public function test_admin_can_dismiss_a_report(): void
    {
        $v = $this->vehicle();
        $report = $v->reports()->create(['source' => 'user', 'reason' => 'scam', 'status' => 'open']);

        $this->actingAs($this->admin())
            ->post(route('admin.moderation.resolve', $report), ['action' => 'dismiss'])
            ->assertRedirect();

        $this->assertSame('dismissed', $report->fresh()->status);
        $this->assertSame('active', $v->fresh()->status); // listing untouched
    }

    public function test_admin_takedown_hides_listing_and_resolves_reports(): void
    {
        $v = $this->vehicle();
        $r1 = $v->reports()->create(['source' => 'user', 'reason' => 'scam', 'status' => 'open']);
        $v->reports()->create(['source' => 'auto', 'reason' => 'prohibited', 'status' => 'open']);

        $this->actingAs($this->admin())
            ->post(route('admin.moderation.resolve', $r1), ['action' => 'takedown'])
            ->assertRedirect();

        $this->assertSame('inactive', $v->fresh()->status);
        $this->assertSame(0, $v->reports()->open()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'moderation.takedown', 'target_id' => $v->id]);
    }

    public function test_non_admin_cannot_reach_moderation_queue(): void
    {
        $seller = User::factory()->create(['role' => 'private_seller', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $seller->assignRole('private_seller');

        $this->actingAs($seller)->get(route('admin.moderation.index'))->assertForbidden();
    }
}
