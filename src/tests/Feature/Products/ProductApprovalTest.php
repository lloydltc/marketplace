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

class ProductApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $user->assignRole('admin');
        return $user;
    }

    private function makeCategory(): Category
    {
        return Category::create([
            'name'        => 'Spare Parts',
            'slug'        => 'spare-parts-' . Str::random(4),
            'sort_order'  => 0,
        ]);
    }

    private function makeVendor(): Vendor
    {
        return Vendor::create([
            'name'          => 'Test Vendor',
            'slug'          => 'test-vendor-' . Str::random(4),
            'contact_email' => 'vendor@test.com',
            'status'        => 'approved',
        ]);
    }

    private function makeProduct(Vendor $vendor, Category $category, string $status = 'pending'): Product
    {
        return Product::create([
            'vendor_id'   => $vendor->id,
            'category_id' => $category->id,
            'title'       => 'Test Brake Pad',
            'description' => 'High-quality brake pad for most sedans.',
            'price_zwl'   => 1500.00,
            'quantity'    => 10,
            'status'      => $status,
        ]);
    }

    public function test_admin_can_approve_pending_product(): void
    {
        $admin    = $this->makeAdmin();
        $vendor   = $this->makeVendor();
        $category = $this->makeCategory();
        $product  = $this->makeProduct($vendor, $category);

        $this->actingAs($admin)
            ->post(route('admin.products.approve', $product))
            ->assertRedirect(route('admin.products.show', $product));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'active']);
    }

    public function test_admin_can_reject_pending_product_with_reason(): void
    {
        $admin    = $this->makeAdmin();
        $vendor   = $this->makeVendor();
        $category = $this->makeCategory();
        $product  = $this->makeProduct($vendor, $category);

        $this->actingAs($admin)
            ->post(route('admin.products.reject', $product), [
                'reason' => 'Title is misleading and does not match description.',
            ])
            ->assertRedirect(route('admin.products.show', $product));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'rejected']);
    }

    public function test_rejection_requires_a_reason(): void
    {
        $admin    = $this->makeAdmin();
        $vendor   = $this->makeVendor();
        $category = $this->makeCategory();
        $product  = $this->makeProduct($vendor, $category);

        $this->actingAs($admin)
            ->post(route('admin.products.reject', $product), ['reason' => ''])
            ->assertSessionHasErrors('reason');
    }

    public function test_non_admin_cannot_approve_products(): void
    {
        $user     = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $vendor   = $this->makeVendor();
        $category = $this->makeCategory();
        $product  = $this->makeProduct($vendor, $category);

        $this->actingAs($user)
            ->post(route('admin.products.approve', $product))
            ->assertForbidden();
    }
}
