<?php

namespace Tests\Feature\Products;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Products\Models\Product;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Products are priced in USD with a seller-set USD→ZWL rate; the ZWL price the
 * engine settles in is derived server-side (price_usd × rate), never entered.
 */
class ProductPricingTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;
    private User $admin;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);

        $this->vendor = Vendor::create([
            'name' => 'Parts Co', 'slug' => 'parts-' . Str::random(5),
            'contact_email' => 'p@x.com', 'status' => 'approved',
        ]);
        $this->admin = User::factory()->create([
            'role' => 'vendor_admin', 'status' => 'active',
            'email_verified_at' => now(), 'force_password_change' => false,
        ]);
        $this->admin->assignRole('vendor_admin');
        $this->vendor->users()->attach($this->admin->id, ['vendor_role' => 'admin', 'joined_at' => now()]);
        $this->category = Category::create(['name' => 'P', 'slug' => 'p-' . Str::random(5), 'sort_order' => 0]);
    }

    private function submit(array $extra): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->post(route('vendor.products.store'), array_merge([
            'category_id' => $this->category->id,
            'title' => 'Spark Plug Set NGK',
            'description' => 'Genuine NGK spark plugs, set of four.',
            'quantity' => 10,
        ], $extra));
    }

    public function test_zwl_price_is_derived_from_usd_times_rate(): void
    {
        $this->submit(['price_usd' => 100, 'exchange_rate' => 36.5])->assertRedirect();

        $product = Product::where('title', 'Spark Plug Set NGK')->first();
        $this->assertSame('100.00', (string) $product->price_usd);
        $this->assertSame('36.5000', (string) $product->exchange_rate);
        $this->assertSame('3650.00', (string) $product->price_zwl); // 100 × 36.5
    }

    public function test_usd_price_is_required(): void
    {
        $this->submit(['exchange_rate' => 36.5])->assertSessionHasErrors('price_usd');
    }

    public function test_exchange_rate_is_required(): void
    {
        $this->submit(['price_usd' => 100])->assertSessionHasErrors('exchange_rate');
    }

    public function test_client_supplied_zwl_is_ignored_and_recomputed(): void
    {
        // Even if a client forges price_zwl, the server recomputes it from usd×rate.
        $this->submit(['price_usd' => 50, 'exchange_rate' => 40, 'price_zwl' => 999999])->assertRedirect();

        $product = Product::where('title', 'Spark Plug Set NGK')->first();
        $this->assertSame('2000.00', (string) $product->price_zwl); // 50 × 40, not 999999
    }

    public function test_display_helpers(): void
    {
        $p = Product::create([
            'vendor_id' => $this->vendor->id, 'category_id' => $this->category->id,
            'title' => 'Helper Item', 'description' => 'x',
            'price_usd' => 100, 'exchange_rate' => 36.5, 'price_zwl' => 3650,
            'quantity' => 1, 'status' => 'active',
        ]);

        $this->assertSame('USD 100.00', $p->primaryPrice());
        $this->assertSame('ZWL 3,650.00', $p->convertedZwl());
        $this->assertSame('1 USD = 36.5 ZWL', $p->rateLabel());
    }
}
