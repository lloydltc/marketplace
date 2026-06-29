<?php

namespace Tests\Feature\Parts;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Cart\DTO\CartGroup;
use App\Modules\Cart\DTO\CartLine;
use App\Modules\Categories\Models\Category;
use App\Modules\Orders\Services\OrderService;
use App\Modules\Parts\Models\Part;
use App\Modules\Parts\Services\PartImporter;
use App\Modules\Products\Exceptions\InsufficientStockException;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Services\InventoryService;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PM11: end-to-end QA gate — select a vehicle → only-compatible parts → part page
 * with seller offers → add to cart → order rides the money spine (commission
 * snapshot) with stock held. Plus the cross-cutting invariants.
 */
class PartsJourneyTest extends TestCase
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
        $this->vendor = Vendor::create(['name' => 'Parts Co', 'slug' => 'pc-' . Str::random(5), 'contact_email' => 'p@x.com', 'status' => 'approved', 'commission_rate' => 10, 'default_fulfilment' => 'vendor']);
        $this->category = Category::create(['name' => 'Brakes', 'slug' => 'brakes-' . Str::random(4), 'sort_order' => 0]);
        $this->make = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
    }

    private function partWithOffer(string $name, array $fitment, int $qty = 10): Product
    {
        $part = Part::create(['name' => $name]);
        if ($fitment) {
            $part->fitments()->create($fitment + ['make_id' => $this->make->id, 'model_id' => $this->model->id]);
        }

        return Product::create([
            'vendor_id' => $this->vendor->id, 'part_id' => $part->id, 'category_id' => $this->category->id,
            'title' => $name, 'description' => 'x', 'price_zwl' => 1000, 'price_usd' => 100,
            'quantity' => $qty, 'status' => 'active', 'fulfilment_type' => 'vendor', 'cod_allowed' => false,
        ]);
    }

    public function test_full_buyer_journey_select_vehicle_to_order(): void
    {
        $fits = $this->partWithOffer('Hilux Brake Pads', ['year_start' => 2015, 'year_end' => 2024]);
        $this->partWithOffer('Mazda-only Pads', []); // no fitment, not universal → never compatible

        // 1. Select the vehicle (fitment context).
        $this->post(route('fitment.select'), ['make_id' => $this->make->id, 'model_id' => $this->model->id, 'year' => 2018])
            ->assertRedirect();

        // 2. Catalog shows only the compatible part.
        $this->get(route('parts.index'))
            ->assertOk()
            ->assertSee('Hilux Brake Pads')
            ->assertDontSee('Mazda-only Pads');

        // 3. Part page shows the seller offer + fits-your-vehicle confirmation.
        $this->get(route('parts.show', $fits->part->slug))
            ->assertOk()
            ->assertSee('Fits your')
            ->assertSee('Seller offers');

        // 4. Add the offer to cart.
        $this->post(route('cart.add'), ['product_id' => $fits->id, 'quantity' => 2])->assertRedirect();
        $this->get(route('cart.index'))->assertOk()->assertSee('Hilux Brake Pads');

        // 5. Order rides the money spine: commission snapshot + stock held.
        $group = new CartGroup($this->vendor->id, $this->vendor->name, 'vendor', [new CartLine($fits, 2)], 0.0, 'Vendor', false);
        [$order] = app(OrderService::class)->createFromCart(
            [$group], [$group->key() => ['fulfilment' => 'vendor', 'payment' => 'prepaid']],
            ['full_name' => 'B', 'email' => 'b@x.com', 'phone' => '+263770000000', 'address' => '1 St', 'city' => 'Harare'], null
        );

        $this->assertGreaterThan(0, (float) $order->commission_amount);
        $this->assertSame((float) $order->subtotal - (float) $order->commission_amount, (float) $order->net_to_vendor);
        $this->assertSame(8, (int) $fits->fresh()->quantity); // 10 - 2 held
    }

    public function test_inventory_never_goes_negative(): void
    {
        $offering = $this->partWithOffer('Pads', ['year_start' => 2015, 'year_end' => 2024], qty: 1);

        $this->expectException(InsufficientStockException::class);
        app(InventoryService::class)->reserve($offering, 5);
    }

    public function test_bulk_imported_fitment_resolves_against_a_vehicle(): void
    {
        $report = app(PartImporter::class)->import([
            ['name' => 'Imported Filter', 'category' => $this->category->slug, 'fitments' => 'Toyota/Hilux/2015/2024'],
        ], dryRun: false);

        $this->assertSame(1, $report['created']);
        $part = Part::where('name', 'Imported Filter')->first();

        $selection = ['make_id' => $this->make->id, 'model_id' => $this->model->id, 'year' => 2018];
        $this->assertTrue($part->fitsSelection($selection));
        $this->assertFalse($part->fitsSelection(['make_id' => $this->make->id, 'model_id' => $this->model->id, 'year' => 2000]));
    }

    public function test_taxonomy_is_shared_with_vehicle_listings(): void
    {
        // The same make/model that fitment narrows on is the one vehicle listings use.
        $this->assertDatabaseHas('vehicle_models', ['id' => $this->model->id, 'make_id' => $this->make->id]);
        $part = $this->partWithOffer('Shared Taxonomy Pad', ['year_start' => 2010, 'year_end' => 2024]);
        $this->assertSame($this->make->id, $part->part->fitments->first()->make_id);
    }
}
