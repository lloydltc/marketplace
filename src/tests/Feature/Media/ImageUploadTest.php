<?php

namespace Tests\Feature\Media;

use App\Models\User;
use App\Models\Vendor;
use App\Jobs\Media\ImageProcessingJob;
use App\Modules\Categories\Models\Category;
use App\Modules\Media\Models\VehicleImage;
use App\Modules\Products\Models\Product;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;
    private User $vendorAdmin;
    private VehicleMake $make;
    private VehicleModel $vehicleModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');
        Queue::fake();

        $this->vendor = Vendor::create([
            'name'          => 'Test Dealer',
            'slug'          => 'test-dealer',
            'contact_email' => 'dealer@test.com',
            'status'        => 'approved',
        ]);

        $this->vendorAdmin = User::factory()->create([
            'role'               => 'vendor_admin',
            'email_verified_at'  => now(),
        ]);
        $this->vendorAdmin->assignRole('vendor_admin');
        $this->vendorAdmin->vendors()->attach($this->vendor->id, ['vendor_role' => 'admin']);

        $this->make         = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota', 'sort_order' => 0]);
        $this->vehicleModel = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Corolla', 'slug' => 'corolla']);
    }

    private function makeVehicle(?string $vendorId = null, ?string $userId = null): Vehicle
    {
        return Vehicle::create([
            'id'           => (string) Str::uuid(),
            'vendor_id'    => $userId !== null ? $vendorId : ($vendorId ?? $this->vendor->id),
            'user_id'      => $userId,
            'make_id'      => $this->make->id,
            'model_id'     => $this->vehicleModel->id,
            'year'         => 2020,
            'body_type'    => 'sedan',
            'transmission' => 'manual',
            'fuel_type'    => 'petrol',
            'mileage'      => 10000,
            'color'        => 'blue',
            'condition'    => 'used',
            'price_zwl'    => 5000000,
            'status'       => 'pending',
        ]);
    }

    // ─── Vendor Vehicle Images ────────────────────────────────────────────────

    public function test_vendor_admin_can_upload_vehicle_image(): void
    {
        $vehicle = $this->makeVehicle();

        $response = $this->actingAs($this->vendorAdmin)
            ->post(route('vendor.vehicles.images.store', $vehicle), [
                'image'     => UploadedFile::fake()->image('photo.jpg', 800, 600),
                'view_type' => 'front',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vehicle_images', ['vehicle_id' => $vehicle->id]);
        Queue::assertPushed(ImageProcessingJob::class);
    }

    public function test_vendor_cannot_upload_to_another_vendors_vehicle(): void
    {
        $otherVendor = Vendor::create([
            'name'          => 'Other Dealer',
            'slug'          => 'other-dealer',
            'contact_email' => 'other@test.com',
            'status'        => 'approved',
        ]);
        $vehicle = $this->makeVehicle($otherVendor->id);

        $response = $this->actingAs($this->vendorAdmin)
            ->post(route('vendor.vehicles.images.store', $vehicle), [
                'image' => UploadedFile::fake()->image('photo.jpg'),
            ]);

        $response->assertForbidden();
    }

    public function test_vendor_can_delete_vehicle_image(): void
    {
        $vehicle = $this->makeVehicle();
        $image   = VehicleImage::create([
            'vehicle_id'    => $vehicle->id,
            'disk'          => 'public',
            'original_path' => 'vehicles/' . $vehicle->id . '/test.jpg',
            'display_order' => 0,
        ]);

        $response = $this->actingAs($this->vendorAdmin)
            ->delete(route('vendor.vehicles.images.destroy', [$vehicle, $image]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('vehicle_images', ['id' => $image->id]);
    }

    public function test_upload_rejects_invalid_mime_type(): void
    {
        $vehicle = $this->makeVehicle();

        $response = $this->actingAs($this->vendorAdmin)
            ->post(route('vendor.vehicles.images.store', $vehicle), [
                'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ]);

        $response->assertSessionHasErrors('image');
        Queue::assertNotPushed(ImageProcessingJob::class);
    }

    // ─── Vendor Product Images ────────────────────────────────────────────────

    public function test_vendor_admin_can_upload_product_image(): void
    {
        $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'sort_order' => 0]);

        $product = Product::create([
            'id'          => (string) Str::uuid(),
            'vendor_id'   => $this->vendor->id,
            'category_id' => $category->id,
            'title'       => 'Test Product',
            'description' => 'A test product',
            'price_zwl'   => 1000,
            'quantity'    => 5,
            'status'      => 'pending',
        ]);

        $response = $this->actingAs($this->vendorAdmin)
            ->post(route('vendor.products.images.store', $product), [
                'image' => UploadedFile::fake()->image('product.jpg', 600, 600),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('product_images', ['product_id' => $product->id]);
        Queue::assertPushed(ImageProcessingJob::class);
    }

    // ─── Seller Vehicle Images ────────────────────────────────────────────────

    public function test_private_seller_can_upload_vehicle_image(): void
    {
        /** @var User $seller */
        $seller = User::factory()->create(['role' => 'private_seller', 'email_verified_at' => now()]);
        $seller->assignRole('private_seller');

        $vehicle = $this->makeVehicle(null, $seller->id);

        $response = $this->actingAs($seller)
            ->post(route('seller.vehicles.images.store', $vehicle), [
                'image'     => UploadedFile::fake()->image('car.jpg', 800, 600),
                'view_type' => 'side',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vehicle_images', ['vehicle_id' => $vehicle->id]);
        Queue::assertPushed(ImageProcessingJob::class);
    }

    public function test_seller_cannot_upload_to_another_sellers_vehicle(): void
    {
        /** @var User $seller */
        $seller = User::factory()->create(['role' => 'private_seller', 'email_verified_at' => now()]);
        /** @var User $otherSeller */
        $otherSeller = User::factory()->create(['role' => 'private_seller', 'email_verified_at' => now()]);
        $seller->assignRole('private_seller');

        $vehicle = $this->makeVehicle(null, $otherSeller->id);

        $response = $this->actingAs($seller)
            ->post(route('seller.vehicles.images.store', $vehicle), [
                'image' => UploadedFile::fake()->image('car.jpg'),
            ]);

        $response->assertForbidden();
    }

    // ─── Tier limit enforcement ───────────────────────────────────────────────

    public function test_unverified_vendor_cannot_exceed_vehicle_image_limit(): void
    {
        $vehicle = $this->makeVehicle();

        // Fill to the limit (5 for unverified)
        VehicleImage::factory()->count(5)->create(['vehicle_id' => $vehicle->id, 'disk' => 'public']);

        $response = $this->actingAs($this->vendorAdmin)
            ->post(route('vendor.vehicles.images.store', $vehicle), [
                'image' => UploadedFile::fake()->image('extra.jpg'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('image');
        Queue::assertNotPushed(ImageProcessingJob::class);
    }
}
