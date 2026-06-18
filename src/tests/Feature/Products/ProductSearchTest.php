<?php

namespace Tests\Feature\Products;

use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Products\Models\Product;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeVendor(): Vendor
    {
        return Vendor::create([
            'name'          => 'Parts Shop',
            'slug'          => 'parts-shop-' . Str::random(4),
            'contact_email' => 'parts@shop.com',
            'status'        => 'approved',
        ]);
    }

    private function makeCategory(): Category
    {
        return Category::create([
            'name'       => 'Spare Parts',
            'slug'       => 'spare-parts-' . Str::random(4),
            'sort_order' => 0,
        ]);
    }

    public function test_public_products_index_shows_only_active_products(): void
    {
        $vendor   = $this->makeVendor();
        $category = $this->makeCategory();

        Product::create([
            'vendor_id' => $vendor->id, 'category_id' => $category->id,
            'title' => 'Active Brake Pads', 'description' => 'Great brake pads.',
            'price_zwl' => 1000, 'quantity' => 5, 'status' => 'active',
        ]);

        Product::create([
            'vendor_id' => $vendor->id, 'category_id' => $category->id,
            'title' => 'Pending Oil Filter', 'description' => 'Filter pending review.',
            'price_zwl' => 500, 'quantity' => 10, 'status' => 'pending',
        ]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('Active Brake Pads');
        $response->assertDontSee('Pending Oil Filter');
    }

    public function test_products_index_filters_by_category(): void
    {
        $vendor    = $this->makeVendor();
        $category1 = $this->makeCategory();
        $category2 = Category::create([
            'name' => 'Tools', 'slug' => 'tools-' . Str::random(4), 'sort_order' => 1,
        ]);

        Product::create([
            'vendor_id' => $vendor->id, 'category_id' => $category1->id,
            'title' => 'Brake Pad', 'description' => 'Brake pad description.',
            'price_zwl' => 1000, 'quantity' => 5, 'status' => 'active',
        ]);

        Product::create([
            'vendor_id' => $vendor->id, 'category_id' => $category2->id,
            'title' => 'Wrench Set', 'description' => 'Professional wrench set.',
            'price_zwl' => 2000, 'quantity' => 3, 'status' => 'active',
        ]);

        $response = $this->get(route('products.index', ['category' => $category2->id]));

        $response->assertOk();
        $response->assertDontSee('Brake Pad');
        $response->assertSee('Wrench Set');
    }

    public function test_product_detail_page_shows_active_product(): void
    {
        $vendor   = $this->makeVendor();
        $category = $this->makeCategory();

        $product = Product::create([
            'vendor_id'   => $vendor->id,
            'category_id' => $category->id,
            'title'       => 'Oil Filter XYZ',
            'description' => 'High-performance oil filter.',
            'price_zwl'   => 800,
            'quantity'    => 15,
            'status'      => 'active',
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Oil Filter XYZ');
    }

    public function test_product_detail_page_returns_404_for_pending_product(): void
    {
        $vendor   = $this->makeVendor();
        $category = $this->makeCategory();

        $product = Product::create([
            'vendor_id'   => $vendor->id,
            'category_id' => $category->id,
            'title'       => 'Draft Product',
            'description' => 'This product is not yet approved.',
            'price_zwl'   => 500,
            'quantity'    => 5,
            'status'      => 'pending',
        ]);

        $this->get(route('products.show', $product))->assertNotFound();
    }
}
