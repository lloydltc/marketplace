<?php

namespace Tests\Feature\Parts;

use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Parts\Models\Part;
use App\Modules\Products\Models\Product;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PM8: side-by-side parts comparison (session-backed, public).
 */
class PartComparisonTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->vendor = Vendor::create(['name' => 'Parts Co', 'slug' => 'pc-' . Str::random(5), 'contact_email' => 'p@x.com', 'status' => 'approved']);
        $this->category = Category::create(['name' => 'Brakes', 'slug' => 'brakes-' . Str::random(4), 'sort_order' => 0]);
    }

    private function part(string $name, float $price): Part
    {
        $part = Part::create(['name' => $name, 'category_id' => $this->category->id]);
        Product::create([
            'vendor_id' => $this->vendor->id, 'part_id' => $part->id, 'category_id' => $this->category->id,
            'title' => $name, 'description' => 'x', 'price_zwl' => $price * 10, 'price_usd' => $price,
            'quantity' => 5, 'status' => 'active',
        ]);

        return $part;
    }

    public function test_add_and_view_compared_parts_with_price_from(): void
    {
        $a = $this->part('Bosch Pads', 30);
        $b = $this->part('TRW Pads', 22);

        $this->post(route('parts.compare.add', $a))->assertRedirect();
        $this->post(route('parts.compare.add', $b))->assertRedirect();

        $this->get(route('parts.compare.show'))
            ->assertOk()
            ->assertSee('Bosch Pads')
            ->assertSee('TRW Pads')
            ->assertSee('USD 30.00')
            ->assertSee('USD 22.00');
    }

    public function test_compare_set_is_capped(): void
    {
        config(['parts.compare_max' => 1]);
        $a = $this->part('Alpha Pad', 10);
        $b = $this->part('Bravo Pad', 12);

        $this->post(route('parts.compare.add', $a));
        $this->post(route('parts.compare.add', $b)); // over cap

        $this->get(route('parts.compare.show'))->assertOk()->assertSee('Alpha Pad')->assertDontSee('Bravo Pad');
    }

    public function test_remove_and_empty_state(): void
    {
        $a = $this->part('Lonely Pad', 10);
        $this->post(route('parts.compare.add', $a));
        $this->delete(route('parts.compare.remove', $a))->assertRedirect();

        $this->get(route('parts.compare.show'))->assertOk()->assertSee('No parts to compare');
    }

    public function test_compare_count_shows_in_catalog(): void
    {
        $a = $this->part('Counted Pad', 10);
        $this->post(route('parts.compare.add', $a));

        $this->get(route('parts.index'))->assertOk()->assertSee('Compare (1)');
    }
}
