<?php

namespace Tests\Feature\Parts;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Cart\DTO\CartGroup;
use App\Modules\Cart\DTO\CartLine;
use App\Modules\Categories\Models\Category;
use App\Modules\Orders\Services\OrderService;
use App\Modules\Parts\Models\Part;
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
 * PM10: parts offerings flow through the existing money spine; ordering a
 * part-offering holds stock and cancelling releases it; part → vehicle cross-sell.
 */
class PartCommerceTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->vendor = Vendor::create(['name' => 'Parts Co', 'slug' => 'pc-' . Str::random(5), 'contact_email' => 'p@x.com', 'status' => 'approved', 'default_fulfilment' => 'vendor']);
        $this->category = Category::create(['name' => 'Brakes', 'slug' => 'brakes-' . Str::random(4), 'sort_order' => 0]);
    }

    private function offering(int $qty = 10): Product
    {
        $part = Part::create(['name' => 'Brake Pads ' . Str::random(4)]);

        return Product::create([
            'vendor_id' => $this->vendor->id, 'part_id' => $part->id, 'category_id' => $this->category->id,
            'title' => 'Brake Pads', 'description' => 'x', 'price_zwl' => 100, 'price_usd' => 10,
            'quantity' => $qty, 'status' => 'active', 'cod_allowed' => false, 'fulfilment_type' => 'vendor',
        ]);
    }

    private function customer(): User
    {
        $u = User::factory()->create(['role' => 'customer', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $u->assignRole('customer');

        return $u;
    }

    private function placeOrder(Product $offering, int $qty, ?string $buyerId): array
    {
        $group = new CartGroup(
            vendorId: $this->vendor->id, vendorName: $this->vendor->name, track: 'vendor',
            lines: [new CartLine($offering, $qty)], deliveryFee: 0.0, deliveryLabel: 'Vendor',
            codAvailable: false,
        );
        $selections = [$group->key() => ['fulfilment' => 'vendor', 'payment' => 'prepaid']];
        $customer = ['full_name' => 'Buyer', 'email' => 'b@x.com', 'phone' => '+263770000000', 'address' => '1 St', 'city' => 'Harare'];

        return app(OrderService::class)->createFromCart([$group], $selections, $customer, $buyerId);
    }

    public function test_offering_can_be_added_to_cart(): void
    {
        $offering = $this->offering();

        $this->post(route('cart.add'), ['product_id' => $offering->id, 'quantity' => 1])->assertRedirect();
        $this->get(route('cart.index'))->assertOk()->assertSee('Brake Pads');
    }

    public function test_ordering_a_part_offering_holds_stock(): void
    {
        $offering = $this->offering(10);

        $this->placeOrder($offering, 3, null);

        $this->assertSame(7, (int) $offering->fresh()->quantity);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $offering->id, 'type' => 'reserve', 'qty' => -3]);
    }

    public function test_cancelling_an_order_releases_held_stock(): void
    {
        $buyer = $this->customer();
        $offering = $this->offering(10);

        [$order] = $this->placeOrder($offering, 4, $buyer->id);
        $this->assertSame(6, (int) $offering->fresh()->quantity);

        $this->actingAs($buyer)->post(route('orders.cancel', $order))->assertRedirect();

        $this->assertSame(10, (int) $offering->fresh()->quantity);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $offering->id, 'type' => 'release', 'qty' => 4]);
    }

    public function test_legacy_product_without_part_is_not_inventory_managed(): void
    {
        // A product with no part_id must not trigger inventory movements (no behaviour change).
        $legacy = Product::create([
            'vendor_id' => $this->vendor->id, 'category_id' => $this->category->id,
            'title' => 'Legacy', 'description' => 'x', 'price_zwl' => 100, 'price_usd' => 10,
            'quantity' => 2, 'status' => 'active', 'fulfilment_type' => 'vendor',
        ]);

        $this->placeOrder($legacy, 5, null); // qty 5 > stock 2, but legacy is unmanaged → no throw

        $this->assertSame(2, (int) $legacy->fresh()->quantity);
        $this->assertSame(0, \App\Modules\Products\Models\InventoryMovement::where('product_id', $legacy->id)->count());
    }

    public function test_part_page_shows_compatible_vehicles_for_sale(): void
    {
        $make = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $model = VehicleModel::create(['make_id' => $make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
        $seller = User::factory()->create(['role' => 'private_seller', 'status' => 'active']);
        $vehicle = Vehicle::create([
            'user_id' => $seller->id, 'make_id' => $make->id, 'model_id' => $model->id, 'year' => 2018,
            'body_type' => 'pickup', 'transmission' => 'manual', 'fuel_type' => 'diesel', 'mileage' => 1000,
            'color' => 'white', 'condition' => 'used', 'price_usd' => 15000, 'vehicle_type' => 'vehicle',
            'status' => 'active', 'expires_at' => now()->addDays(10),
        ]);

        $part = Part::create(['name' => 'Hilux Pads']);
        $part->fitments()->create(['make_id' => $make->id, 'model_id' => $model->id, 'year_start' => 2015, 'year_end' => 2024]);
        Product::create(['vendor_id' => $this->vendor->id, 'part_id' => $part->id, 'category_id' => $this->category->id,
            'title' => 'Pads', 'description' => 'x', 'price_zwl' => 100, 'price_usd' => 10, 'quantity' => 5, 'status' => 'active']);

        $this->get(route('parts.show', $part->slug))
            ->assertOk()
            ->assertSee('Compatible vehicles for sale')
            ->assertSee('2018 Toyota Hilux');
    }
}
