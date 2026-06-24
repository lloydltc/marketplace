<?php

namespace Tests\Feature\Dealers;

use App\Models\AuditLog;
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
 * H8: public dealer directory + storefronts and admin featured placement.
 */
class DealerStorefrontTest extends TestCase
{
    use RefreshDatabase;

    private VehicleMake $make;
    private VehicleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->make = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $this->model = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
    }

    private function dealer(string $name, array $attrs = []): Vendor
    {
        return Vendor::create(array_merge([
            'name' => $name, 'slug' => Str::slug($name) . '-' . Str::random(4),
            'contact_email' => Str::random(5) . '@x.com', 'status' => 'approved',
        ], $attrs));
    }

    private function vendorVehicle(Vendor $vendor, array $attrs = []): Vehicle
    {
        return Vehicle::create(array_merge([
            'vendor_id' => $vendor->id, 'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2021, 'body_type' => 'sedan', 'transmission' => 'manual', 'fuel_type' => 'petrol',
            'mileage' => 1000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 15000,
            'vehicle_type' => 'vehicle', 'status' => 'active', 'expires_at' => now()->addDays(10),
        ], $attrs));
    }

    private function vendorProduct(Vendor $vendor, array $attrs = []): Product
    {
        $cat = Category::create(['name' => 'Parts', 'slug' => 'parts-' . Str::random(4), 'sort_order' => 0]);

        return Product::create(array_merge([
            'vendor_id' => $vendor->id, 'category_id' => $cat->id,
            'title' => 'Brake Pads', 'description' => 'x', 'price_zwl' => 100, 'quantity' => 5, 'status' => 'active',
        ], $attrs));
    }

    public function test_directory_lists_only_approved_dealers(): void
    {
        $this->dealer('Approved Motors');
        $this->dealer('Pending Motors', ['status' => 'pending']);

        $this->get(route('dealers.index'))
            ->assertOk()
            ->assertSee('Approved Motors')
            ->assertDontSee('Pending Motors');
    }

    public function test_directory_search_filters_by_name(): void
    {
        $this->dealer('Harare Auto');
        $this->dealer('Bulawayo Cars');

        $this->get(route('dealers.index', ['q' => 'Harare']))
            ->assertOk()
            ->assertSee('Harare Auto')
            ->assertDontSee('Bulawayo Cars');
    }

    public function test_featured_carousel_shows_featured_dealers(): void
    {
        $this->dealer('Star Dealership', ['featured_until' => now()->addDays(10)]);
        $this->dealer('Plain Dealership');

        $res = $this->get(route('dealers.index'))->assertOk();
        $res->assertSee('Featured dealers');
        $res->assertSee('Star Dealership');
    }

    public function test_storefront_shows_dealer_inventory(): void
    {
        $dealer = $this->dealer('Inventory Motors');
        $this->vendorVehicle($dealer, ['year' => 2022]);
        $this->vendorProduct($dealer);

        $this->get(route('dealers.show', $dealer->slug))
            ->assertOk()
            ->assertSee('Inventory Motors')
            ->assertSee('2022 Toyota Hilux')
            ->assertSee('Brake Pads');
    }

    public function test_storefront_excludes_non_active_listings(): void
    {
        $dealer = $this->dealer('Mixed Motors');
        $this->vendorVehicle($dealer, ['year' => 2017, 'status' => 'pending']);

        $this->get(route('dealers.show', $dealer->slug))
            ->assertOk()
            ->assertDontSee('2017 Toyota Hilux');
    }

    public function test_non_approved_dealer_storefront_is_not_found(): void
    {
        $dealer = $this->dealer('Hidden Motors', ['status' => 'suspended']);

        $this->get(route('dealers.show', $dealer->slug))->assertNotFound();
    }

    public function test_admin_can_feature_and_unfeature_a_dealer(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $admin->assignRole('admin');
        $dealer = $this->dealer('Promote Me');

        $this->actingAs($admin)
            ->post(route('admin.vendors.feature', $dealer), ['days' => 30])
            ->assertRedirect();

        $this->assertTrue($dealer->fresh()->isFeaturedDealer());
        $this->assertDatabaseHas('audit_logs', ['action' => 'dealer.featured', 'target_id' => $dealer->id]);

        $this->actingAs($admin)
            ->delete(route('admin.vendors.unfeature', $dealer))
            ->assertRedirect();

        $this->assertFalse($dealer->fresh()->isFeaturedDealer());
    }

    public function test_pending_dealer_cannot_be_featured(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $admin->assignRole('admin');
        $dealer = $this->dealer('Not Yet', ['status' => 'pending']);

        $this->actingAs($admin)
            ->post(route('admin.vendors.feature', $dealer), ['days' => 30])
            ->assertStatus(422);
    }
}
