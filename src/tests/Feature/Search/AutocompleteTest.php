<?php

namespace Tests\Feature\Search;

use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Products\Models\Product;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AutocompleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_product_autocomplete_returns_matching_titles(): void
    {
        $vendor   = Vendor::create(['name' => 'Shop', 'slug' => 'shop-' . Str::random(4), 'contact_email' => 's@x.com', 'status' => 'approved']);
        $category = Category::create(['name' => 'Parts', 'slug' => 'parts-' . Str::random(4), 'sort_order' => 0]);

        Product::create([
            'vendor_id' => $vendor->id, 'category_id' => $category->id,
            'title' => 'Toyota Brake Pads', 'description' => 'desc', 'price_zwl' => 100,
            'quantity' => 5, 'status' => 'active',
        ]);

        $this->getJson(route('search.products', ['q' => 'Toyo']))
            ->assertOk()
            ->assertJsonFragment(['Toyota Brake Pads']);
    }

    public function test_product_autocomplete_excludes_inactive(): void
    {
        $vendor   = Vendor::create(['name' => 'Shop', 'slug' => 'shop-' . Str::random(4), 'contact_email' => 's@x.com', 'status' => 'approved']);
        $category = Category::create(['name' => 'Parts', 'slug' => 'parts-' . Str::random(4), 'sort_order' => 0]);

        Product::create([
            'vendor_id' => $vendor->id, 'category_id' => $category->id,
            'title' => 'Hidden Pending Item', 'description' => 'desc', 'price_zwl' => 100,
            'quantity' => 5, 'status' => 'pending',
        ]);

        $this->getJson(route('search.products', ['q' => 'Hidden']))
            ->assertOk()
            ->assertJsonMissing(['Hidden Pending Item']);
    }

    public function test_short_query_returns_empty(): void
    {
        $this->getJson(route('search.products', ['q' => 'a']))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_vehicle_autocomplete_returns_make_model_pairs(): void
    {
        $make  = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        VehicleModel::create(['make_id' => $make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);

        $this->getJson(route('search.vehicles', ['q' => 'Hilux']))
            ->assertOk()
            ->assertJsonFragment(['Toyota Hilux']);
    }
}
