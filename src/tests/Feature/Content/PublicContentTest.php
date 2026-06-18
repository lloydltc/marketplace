<?php

namespace Tests\Feature\Content;

use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Products\Models\Product;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * P9: public content/legal pages, settings-driven fees, sitemap, and SEO
 * structured data + meta on listing detail pages.
 */
class PublicContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
    }

    public function test_legal_and_content_pages_render(): void
    {
        foreach (['pages.terms', 'pages.privacy', 'pages.cod-policy',
                  'pages.how-fbs-works', 'pages.rfq-guide', 'pages.fees'] as $name) {
            $this->get(route($name))->assertOk();
        }
    }

    public function test_fees_page_reflects_platform_settings(): void
    {
        app(SettingsService::class)->set('commission.default_rate', 12.5);

        $this->get(route('pages.fees'))
            ->assertOk()
            ->assertSee('12.5%');
    }

    public function test_footer_links_to_legal_pages(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('pages.terms'))
            ->assertSee(route('pages.privacy'));
    }

    public function test_sitemap_returns_xml_with_listings(): void
    {
        $vendor = Vendor::create([
            'name' => 'V', 'slug' => 'v-' . Str::random(5),
            'contact_email' => 'v@x.com', 'status' => 'approved',
        ]);
        $cat = Category::create(['name' => 'P', 'slug' => 'p-' . Str::random(5), 'sort_order' => 0]);
        $product = Product::create([
            'vendor_id' => $vendor->id, 'category_id' => $cat->id,
            'title' => 'Sitemap Part', 'description' => 'x',
            'price_zwl' => 100, 'quantity' => 5, 'status' => 'active',
        ]);

        $res = $this->get('/sitemap.xml')->assertOk();
        $res->assertHeader('Content-Type', 'application/xml');
        $res->assertSee('<urlset', false);
        $res->assertSee(route('products.show', $product), false);
    }

    public function test_product_detail_has_structured_data_and_meta(): void
    {
        $vendor = Vendor::create([
            'name' => 'V', 'slug' => 'v-' . Str::random(5),
            'contact_email' => 'v@x.com', 'status' => 'approved',
        ]);
        $cat = Category::create(['name' => 'P', 'slug' => 'p-' . Str::random(5), 'sort_order' => 0]);
        $product = Product::create([
            'vendor_id' => $vendor->id, 'category_id' => $cat->id,
            'title' => 'SEO Part', 'description' => 'A great part for your car engine bay.',
            'price_zwl' => 100, 'quantity' => 5, 'status' => 'active',
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('<meta name="description"', false)
            ->assertSee('og:title', false);
    }
}
