<?php

namespace Tests\Feature\Products;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Products\Models\Product;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeVendorAdmin(Vendor $vendor): User
    {
        $user = User::factory()->create(['role' => 'vendor_admin', 'email_verified_at' => now()]);
        $user->assignRole('vendor_admin');
        $vendor->users()->attach($user->id, [
            'vendor_role' => 'admin',
            'invited_at'  => now(),
            'joined_at'   => now(),
        ]);
        return $user;
    }

    private function makeApprovedVendor(): Vendor
    {
        return Vendor::create([
            'name'          => 'AutoParts ZW',
            'slug'          => 'autoparts-' . Str::random(4),
            'contact_email' => 'shop@autoparts.zw',
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

    public function test_vendor_admin_can_create_product(): void
    {
        $vendor   = $this->makeApprovedVendor();
        $user     = $this->makeVendorAdmin($vendor);
        $category = $this->makeCategory();

        $this->actingAs($user)
            ->post(route('vendor.products.store'), [
                'category_id' => $category->id,
                'title'       => 'Toyota Hilux Brake Pad Set',
                'description' => 'OEM quality brake pads for Toyota Hilux 2015-2023 models.',
                'price_zwl'   => 4500.00,
                'quantity'    => 20,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'vendor_id'   => $vendor->id,
            'category_id' => $category->id,
            'title'       => 'Toyota Hilux Brake Pad Set',
        ]);
    }

    public function test_pending_vendor_can_list_while_unverified(): void
    {
        // Remediation R4/F13: a pending vendor may build inventory. The listing is
        // created (pending) and badged "unverified"; it is not transactable until
        // approval (gated at cart/checkout — covered by CartController tests).
        $vendor = Vendor::create([
            'name'          => 'New Vendor',
            'slug'          => 'new-vendor-' . Str::random(4),
            'contact_email' => 'new@vendor.com',
            'status'        => 'pending',
        ]);
        $user     = $this->makeVendorAdmin($vendor);
        $category = $this->makeCategory();

        $this->actingAs($user)
            ->post(route('vendor.products.store'), [
                'category_id' => $category->id,
                'title'       => 'Some Product Name Here',
                'description' => 'A detailed description of the product.',
                'price_zwl'   => 1000.00,
                'quantity'    => 5,
            ])
            ->assertRedirect();

        // Listing is created; transactability is gated on the vendor's verification
        // status (canTransact), not on the product row — see ListWhileUnverifiedTest.
        $this->assertDatabaseHas('products', [
            'vendor_id' => $vendor->id,
            'title'     => 'Some Product Name Here',
        ]);
    }

    public function test_product_title_must_be_at_least_5_characters(): void
    {
        $vendor   = $this->makeApprovedVendor();
        $user     = $this->makeVendorAdmin($vendor);
        $category = $this->makeCategory();

        $this->actingAs($user)
            ->post(route('vendor.products.store'), [
                'category_id' => $category->id,
                'title'       => 'Hi',
                'description' => 'A detailed description of this product listing.',
                'price_zwl'   => 1000.00,
                'quantity'    => 5,
            ])
            ->assertSessionHasErrors('title');
    }

    public function test_vendor_can_delete_their_own_product(): void
    {
        $vendor   = $this->makeApprovedVendor();
        $user     = $this->makeVendorAdmin($vendor);
        $category = $this->makeCategory();

        $product = Product::create([
            'vendor_id'   => $vendor->id,
            'category_id' => $category->id,
            'title'       => 'Delete Me Product',
            'description' => 'Product to be deleted.',
            'price_zwl'   => 500.00,
            'quantity'    => 1,
            'status'      => 'pending',
        ]);

        $this->actingAs($user)
            ->delete(route('vendor.products.destroy', $product))
            ->assertRedirect(route('vendor.products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_vendor_cannot_delete_another_vendors_product(): void
    {
        $vendor1   = $this->makeApprovedVendor();
        $vendor2   = Vendor::create([
            'name'          => 'Other Vendor',
            'slug'          => 'other-vendor-' . Str::random(4),
            'contact_email' => 'other@vendor.com',
            'status'        => 'approved',
        ]);
        $user1    = $this->makeVendorAdmin($vendor1);
        $category = $this->makeCategory();

        $product = Product::create([
            'vendor_id'   => $vendor2->id,
            'category_id' => $category->id,
            'title'       => 'Other Vendor Product',
            'description' => 'This belongs to another vendor.',
            'price_zwl'   => 999.00,
            'quantity'    => 5,
            'status'      => 'pending',
        ]);

        $this->actingAs($user1)
            ->delete(route('vendor.products.destroy', $product))
            ->assertForbidden();
    }
}
