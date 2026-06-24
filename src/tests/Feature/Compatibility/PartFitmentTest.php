<?php

namespace Tests\Feature\Compatibility;

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
 * H10: parts ⇄ vehicle cross-sell compatibility — matching in both directions
 * and vendor fitment management.
 */
class PartFitmentTest extends TestCase
{
    use RefreshDatabase;

    private VehicleMake $toyota;
    private VehicleModel $hilux;
    private VehicleMake $mazda;
    private Vendor $vendor;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        $this->toyota = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $this->hilux  = VehicleModel::create(['make_id' => $this->toyota->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
        $this->mazda  = VehicleMake::create(['name' => 'Mazda', 'slug' => 'mazda-' . Str::random(4), 'sort_order' => 1]);
        $this->vendor = Vendor::create(['name' => 'Parts Co', 'slug' => 'parts-' . Str::random(5), 'contact_email' => 'p@x.com', 'status' => 'approved']);
        $this->category = Category::create(['name' => 'Brakes', 'slug' => 'brakes-' . Str::random(4), 'sort_order' => 0]);
    }

    private function vehicle(array $attrs = []): Vehicle
    {
        return Vehicle::create(array_merge([
            'vendor_id' => $this->vendor->id, 'make_id' => $this->toyota->id, 'model_id' => $this->hilux->id,
            'year' => 2016, 'body_type' => 'pickup', 'transmission' => 'manual', 'fuel_type' => 'diesel',
            'mileage' => 1000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 15000,
            'vehicle_type' => 'vehicle', 'status' => 'active', 'expires_at' => now()->addDays(10),
        ], $attrs));
    }

    private function part(string $title, array $fitments = []): Product
    {
        $p = Product::create([
            'vendor_id' => $this->vendor->id, 'category_id' => $this->category->id,
            'title' => $title, 'description' => 'x', 'price_usd' => 50, 'price_zwl' => 1000, 'quantity' => 5, 'status' => 'active',
        ]);
        foreach ($fitments as $f) {
            $p->fitments()->create($f);
        }

        return $p;
    }

    public function test_part_matches_vehicle_within_year_range(): void
    {
        $vehicle = $this->vehicle(['year' => 2016]);
        $fits = $this->part('Hilux Brake Pads', [['make_id' => $this->toyota->id, 'model_id' => $this->hilux->id, 'year_from' => 2015, 'year_to' => 2018]]);
        $this->part('Mazda Pads', [['make_id' => $this->mazda->id]]);
        $this->part('Old Hilux Pads', [['make_id' => $this->toyota->id, 'model_id' => $this->hilux->id, 'year_from' => 2005, 'year_to' => 2010]]);

        $matches = Product::active()->compatibleWithVehicle($vehicle)->pluck('id');

        $this->assertTrue($matches->contains($fits->id));
        $this->assertCount(1, $matches);
    }

    public function test_any_make_fitment_matches_everything(): void
    {
        $vehicle = $this->vehicle();
        $universal = $this->part('Universal Mat', [['make_id' => null, 'model_id' => null, 'year_from' => null, 'year_to' => null]]);

        $this->assertTrue(
            Product::active()->compatibleWithVehicle($vehicle)->pluck('id')->contains($universal->id)
        );
    }

    public function test_vehicle_show_lists_compatible_parts(): void
    {
        $vehicle = $this->vehicle(['year' => 2016]);
        $this->part('Hilux Brake Pads', [['make_id' => $this->toyota->id, 'model_id' => $this->hilux->id, 'year_from' => 2015, 'year_to' => 2018]]);

        $this->get(route('vehicles.show', $vehicle))
            ->assertOk()
            ->assertSee('Parts that fit this')
            ->assertSee('Hilux Brake Pads');
    }

    public function test_part_show_lists_fitments_and_compatible_vehicles(): void
    {
        $vehicle = $this->vehicle(['year' => 2016]);
        $part = $this->part('Hilux Brake Pads', [['make_id' => $this->toyota->id, 'model_id' => $this->hilux->id, 'year_from' => 2015, 'year_to' => 2018]]);

        $this->get(route('products.show', $part))
            ->assertOk()
            ->assertSee('Fits these vehicles')
            ->assertSee('Compatible vehicles for sale')
            ->assertSee($vehicle->displayTitle());
    }

    public function test_part_show_excludes_out_of_range_vehicles(): void
    {
        $this->vehicle(['year' => 2002]); // out of the 2015–2018 range
        $part = $this->part('Hilux Brake Pads', [['make_id' => $this->toyota->id, 'model_id' => $this->hilux->id, 'year_from' => 2015, 'year_to' => 2018]]);

        $this->get(route('products.show', $part))
            ->assertOk()
            ->assertDontSee('Compatible vehicles for sale');
    }

    public function test_vendor_can_add_and_remove_fitment(): void
    {
        $admin = User::factory()->create(['role' => 'vendor_admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $admin->assignRole('vendor_admin');
        $this->vendor->users()->attach($admin->id, ['vendor_role' => 'admin', 'joined_at' => now()]);
        $part = $this->part('Pads'); // no fitments yet

        $this->actingAs($admin)
            ->post(route('vendor.products.fitments.store', $part), [
                'make_id' => $this->toyota->id, 'model_id' => $this->hilux->id, 'year_from' => 2015, 'year_to' => 2018,
            ])->assertRedirect();

        $this->assertSame(1, $part->fitments()->count());

        $fitment = $part->fitments()->first();
        $this->actingAs($admin)
            ->delete(route('vendor.products.fitments.destroy', [$part, $fitment]))
            ->assertRedirect();

        $this->assertSame(0, $part->fitments()->count());
    }

    public function test_vendor_cannot_manage_another_vendors_fitments(): void
    {
        $otherVendor = Vendor::create(['name' => 'Other', 'slug' => 'other-' . Str::random(5), 'contact_email' => 'o@x.com', 'status' => 'approved']);
        $admin = User::factory()->create(['role' => 'vendor_admin', 'status' => 'active', 'email_verified_at' => now(), 'force_password_change' => false]);
        $admin->assignRole('vendor_admin');
        $otherVendor->users()->attach($admin->id, ['vendor_role' => 'admin', 'joined_at' => now()]);

        $part = $this->part('Pads'); // belongs to $this->vendor

        $this->actingAs($admin)
            ->post(route('vendor.products.fitments.store', $part), ['make_id' => $this->toyota->id])
            ->assertForbidden();
    }
}
