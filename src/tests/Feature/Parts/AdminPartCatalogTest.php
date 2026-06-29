<?php

namespace Tests\Feature\Parts;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Parts\Models\Part;
use App\Modules\Parts\Services\PartImporter;
use App\Modules\Parts\Services\PartMerger;
use App\Modules\Products\Models\Product;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PM9: admin catalog CRUD + fitment authoring, CSV import (dry-run + errors),
 * and duplicate merge — all audited.
 */
class AdminPartCatalogTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private VehicleMake $make;
    private VehicleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->category = Category::create(['name' => 'Brakes', 'slug' => 'brakes', 'sort_order' => 0]);
        $this->make = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
    }

    private function admin(): User
    {
        $u = User::factory()->create(['role' => 'admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('admin');

        return $u;
    }

    // ─── CRUD + authoring ───────────────────────────────────────────────────────

    public function test_admin_can_create_a_part(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.parts.store'), ['name' => 'Brake Pad Set', 'category_id' => $this->category->id, 'status' => 'active'])
            ->assertRedirect();

        $this->assertDatabaseHas('parts', ['name' => 'Brake Pad Set', 'slug' => 'brake-pad-set']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'catalog.part.create']);
    }

    public function test_admin_can_author_fitment_with_range(): void
    {
        $part = Part::create(['name' => 'Pads']);

        $this->actingAs($this->admin())
            ->post(route('admin.parts.fitments.add', $part), [
                'make_id' => $this->make->id, 'model_id' => $this->model->id, 'year_start' => 2015, 'year_end' => 2024,
            ])->assertRedirect();

        $this->assertDatabaseHas('part_fitments', ['part_id' => $part->id, 'year_start' => 2015, 'year_end' => 2024]);
    }

    public function test_non_admin_cannot_reach_catalog(): void
    {
        $seller = User::factory()->create(['role' => 'private_seller', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $seller->assignRole('private_seller');

        $this->actingAs($seller)->get(route('admin.parts.index'))->assertForbidden();
    }

    // ─── CSV import ───────────────────────────────────────────────────────────────

    public function test_import_dry_run_validates_without_creating(): void
    {
        $rows = [
            ['name' => 'Good Part', 'category' => 'brakes', 'fitments' => 'Toyota/Hilux/2015/2024'],
            ['name' => '', 'category' => 'brakes'],                              // missing name
            ['name' => 'Bad Cat', 'category' => 'nope'],                         // unknown category
            ['name' => 'Bad Fitment', 'fitments' => 'Ford/Unknown/2010/2012'],   // unknown vehicle
        ];

        $report = app(PartImporter::class)->import($rows, dryRun: true);

        $this->assertTrue($report['dry_run']);
        $this->assertSame(0, $report['created']);
        $this->assertSame(1, $report['valid']);
        $this->assertCount(3, $report['errors']);
        $this->assertSame(0, Part::count());
    }

    public function test_import_commit_creates_parts_oem_and_fitment(): void
    {
        $rows = [
            ['name' => 'Front Pads', 'brand' => 'Bosch', 'category' => 'brakes',
             'oem_numbers' => '0446-1|0446-2', 'fitments' => 'Toyota/Hilux/2015/2024', 'is_universal' => '0'],
        ];

        $report = app(PartImporter::class)->import($rows, dryRun: false);

        $this->assertSame(1, $report['created']);
        $part = Part::where('name', 'Front Pads')->first();
        $this->assertNotNull($part);
        $this->assertSame(2, $part->oemNumbers()->count());
        $this->assertSame(1, $part->fitments()->count());
    }

    public function test_admin_import_endpoint_reports_via_upload(): void
    {
        $csv = "name,category,fitments\nUploaded Pad,brakes,Toyota/Hilux/2016/2020\n";
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('parts.csv', $csv);

        $this->actingAs($this->admin())
            ->post(route('admin.parts.import.process'), ['csv' => $file, 'action' => 'import'])
            ->assertOk()
            ->assertSee('Import result');

        $this->assertDatabaseHas('parts', ['name' => 'Uploaded Pad']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'catalog.part.import']);
    }

    // ─── Merge ──────────────────────────────────────────────────────────────────

    public function test_merge_moves_offerings_oem_and_fitments(): void
    {
        $vendor = Vendor::create(['name' => 'V', 'slug' => 'v-' . Str::random(5), 'contact_email' => 'v@x.com', 'status' => 'approved']);
        $keeper = Part::create(['name' => 'Keeper Pads']);
        $dupe = Part::create(['name' => 'Duplicate Pads']);
        $dupe->oemNumbers()->create(['number' => 'DUP-1', 'type' => 'oem']);
        $dupe->fitments()->create(['make_id' => $this->make->id, 'model_id' => $this->model->id, 'year_start' => 2015, 'year_end' => 2020]);
        $offering = Product::create(['vendor_id' => $vendor->id, 'part_id' => $dupe->id, 'category_id' => $this->category->id,
            'title' => 'Dup offer', 'description' => 'x', 'price_zwl' => 100, 'price_usd' => 10, 'quantity' => 3, 'status' => 'active']);

        app(PartMerger::class)->merge($keeper, $dupe);

        $this->assertSame($keeper->id, $offering->fresh()->part_id);
        $this->assertSame(1, $keeper->oemNumbers()->count());
        $this->assertSame(1, $keeper->fitments()->count());
        $this->assertSoftDeleted('parts', ['id' => $dupe->id]);
    }

    public function test_admin_merge_endpoint_is_audited(): void
    {
        $keeper = Part::create(['name' => 'Keeper']);
        $dupe = Part::create(['name' => 'Dupe']);

        $this->actingAs($this->admin())
            ->post(route('admin.parts.merge'), ['keeper_id' => $keeper->id, 'duplicate_id' => $dupe->id])
            ->assertRedirect();

        $this->assertSoftDeleted('parts', ['id' => $dupe->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'catalog.part.merge', 'target_id' => $keeper->id]);
    }
}
