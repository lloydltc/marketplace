<?php

namespace Tests\Feature\Parts;

use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Parts\Models\Part;
use App\Modules\Parts\Services\VinDecoder;
use App\Modules\Products\Models\Product;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PM4: parts catalog browse — fitment filter, facets, keyword/OEM, VIN decode.
 */
class PartCatalogBrowseTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;
    private Category $category;
    private VehicleMake $make;
    private VehicleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->vendor = Vendor::create(['name' => 'Parts Co', 'slug' => 'pc-' . Str::random(5), 'contact_email' => 'p@x.com', 'status' => 'approved']);
        $this->category = Category::create(['name' => 'Brakes', 'slug' => 'brakes-' . Str::random(4), 'sort_order' => 0]);
        $this->make = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
    }

    private function partWithOffer(array $partAttrs = [], float $price = 25, int $qty = 5): Part
    {
        $part = Part::create(array_merge(['name' => 'Brake Pads', 'category_id' => $this->category->id], $partAttrs));
        Product::create([
            'vendor_id' => $this->vendor->id, 'part_id' => $part->id, 'category_id' => $this->category->id,
            'title' => $part->name, 'description' => 'x', 'price_zwl' => $price * 10, 'price_usd' => $price,
            'quantity' => $qty, 'status' => 'active',
        ]);

        return $part;
    }

    public function test_browse_lists_only_parts_with_active_offers_with_price_from(): void
    {
        $this->partWithOffer(['name' => 'Sold Pads'], 25);
        Part::create(['name' => 'Orphan Part', 'category_id' => $this->category->id]); // no offering

        $this->get(route('parts.index'))
            ->assertOk()
            ->assertSee('Sold Pads')
            ->assertSee('from USD 25.00')
            ->assertDontSee('Orphan Part');
    }

    public function test_keyword_searches_name_brand_and_oem(): void
    {
        $a = $this->partWithOffer(['name' => 'Front Disc', 'brand' => 'Brembo']);
        $a->oemNumbers()->create(['number' => 'BR-9988', 'type' => 'oem']);
        $this->partWithOffer(['name' => 'Air Filter', 'brand' => 'Mann']);

        $this->get(route('parts.index', ['q' => 'BR-9988']))->assertOk()->assertSee('Front Disc')->assertDontSee('Air Filter');
        $this->get(route('parts.index', ['q' => 'Brembo']))->assertOk()->assertSee('Front Disc');
    }

    public function test_category_facet_filters(): void
    {
        $other = Category::create(['name' => 'Engine', 'slug' => 'engine-' . Str::random(4), 'sort_order' => 1]);
        $this->partWithOffer(['name' => 'Brake Thing', 'category_id' => $this->category->id]);
        $this->partWithOffer(['name' => 'Engine Thing', 'category_id' => $other->id]);

        $this->get(route('parts.index', ['category' => $this->category->id]))
            ->assertOk()->assertSee('Brake Thing')->assertDontSee('Engine Thing');
    }

    public function test_fitment_context_filters_to_compatible_parts(): void
    {
        $fits = $this->partWithOffer(['name' => 'Hilux Pads']);
        $fits->fitments()->create(['make_id' => $this->make->id, 'model_id' => $this->model->id, 'year_start' => 2015, 'year_end' => 2024]);
        $this->partWithOffer(['name' => 'Unrelated Pads']); // no fitment, not universal

        // Select the vehicle, then browse.
        $this->post(route('fitment.select'), ['make_id' => $this->make->id, 'model_id' => $this->model->id, 'year' => 2018]);

        $this->get(route('parts.index'))
            ->assertOk()
            ->assertSee('Hilux Pads')
            ->assertSee('Showing parts for')
            ->assertDontSee('Unrelated Pads');
    }

    public function test_vin_decoder_extracts_year_and_make_hint(): void
    {
        // WMI "JTE" (Toyota) + year code 'J' (2018) at position 10.
        $decoded = app(VinDecoder::class)->decode('JTEBU5JR8J5123456');
        $this->assertTrue($decoded['valid']);
        $this->assertSame(2018, $decoded['year']);
        $this->assertSame('Toyota', $decoded['make_hint']);

        $this->assertFalse(app(VinDecoder::class)->decode('TOOSHORT')['valid']);
    }

    public function test_vin_search_redirects_to_catalog(): void
    {
        $this->post(route('parts.vin'), ['vin' => 'JTEBU5JR8J5123456'])
            ->assertRedirect(route('parts.index'))
            ->assertSessionHas('status');

        $this->post(route('parts.vin'), ['vin' => 'bad'])
            ->assertSessionHasErrors('vin');
    }

    public function test_empty_results_show_rfq_cta(): void
    {
        $this->get(route('parts.index', ['q' => 'nonexistent-zzz']))
            ->assertOk()
            ->assertSee('No parts match')
            ->assertSee(route('rfq.create', ['q' => 'nonexistent-zzz', 'for' => 'parts']));
    }
}
