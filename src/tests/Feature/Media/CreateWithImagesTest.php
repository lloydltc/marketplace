<?php

namespace Tests\Feature\Media;

use App\Jobs\Media\ImageProcessingJob;
use App\Models\User;
use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * P2/G6: images can now be attached at CREATE time (not only on edit), for both
 * vendor and private-seller flows, and the secure pipeline rejects non-images by
 * content (not just extension).
 */
class CreateWithImagesTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;
    private User $vendorAdmin;
    private VehicleMake $make;
    private VehicleModel $model;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        Storage::fake('public');
        Queue::fake();

        $this->vendor = Vendor::create([
            'name' => 'Dealer', 'slug' => 'dealer-' . Str::random(4),
            'contact_email' => 'd@x.com', 'status' => 'approved',
        ]);
        $this->vendorAdmin = User::factory()->create([
            'role' => 'vendor_admin', 'status' => 'active',
            'email_verified_at' => now(), 'force_password_change' => false,
        ]);
        $this->vendorAdmin->assignRole('vendor_admin');
        $this->vendor->users()->attach($this->vendorAdmin->id, ['vendor_role' => 'admin', 'joined_at' => now()]);

        $this->make     = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $this->model    = VehicleModel::create(['make_id' => $this->make->id, 'name' => 'Corolla', 'slug' => 'corolla-' . Str::random(4)]);
        $this->category = Category::create(['name' => 'Parts', 'slug' => 'parts-' . Str::random(4), 'sort_order' => 0]);
    }

    private function vehiclePayload(array $extra = []): array
    {
        return array_merge([
            'make_id' => $this->make->id, 'model_id' => $this->model->id,
            'year' => 2020, 'body_type' => 'sedan', 'transmission' => 'manual',
            'fuel_type' => 'petrol', 'mileage' => 10000, 'color' => 'blue',
            'condition' => 'used', 'price_zwl' => 5000000,
        ], $extra);
    }

    public function test_vendor_can_create_product_with_images(): void
    {
        $this->actingAs($this->vendorAdmin)->post(route('vendor.products.store'), [
            'category_id' => $this->category->id,
            'title' => 'Brake Pad Set',
            'description' => 'A high quality brake pad set for sedans.',
            'price_zwl' => 1000, 'quantity' => 5,
            'images' => [
                UploadedFile::fake()->image('a.jpg', 800, 600),
                UploadedFile::fake()->image('b.png', 640, 480),
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('products', ['title' => 'Brake Pad Set']);
        $this->assertSame(2, \App\Modules\Media\Models\ProductImage::count());
        Queue::assertPushed(ImageProcessingJob::class, 2);
    }

    public function test_vendor_can_create_vehicle_with_images(): void
    {
        $this->actingAs($this->vendorAdmin)->post(route('vendor.vehicles.store'), $this->vehiclePayload([
            'images' => [UploadedFile::fake()->image('car1.jpg', 800, 600)],
        ]))->assertRedirect();

        $this->assertSame(1, \App\Modules\Media\Models\VehicleImage::count());
        Queue::assertPushed(ImageProcessingJob::class, 1);
    }

    public function test_private_seller_can_create_vehicle_with_images(): void
    {
        $seller = User::factory()->create([
            'role' => 'private_seller', 'status' => 'active',
            'email_verified_at' => now(), 'force_password_change' => false,
        ]);
        $seller->assignRole('private_seller');

        $this->actingAs($seller)->post(route('seller.vehicles.store'), $this->vehiclePayload([
            'images' => [
                UploadedFile::fake()->image('s1.jpg', 800, 600),
                UploadedFile::fake()->image('s2.jpg', 800, 600),
            ],
        ]))->assertRedirect();

        $this->assertSame(2, \App\Modules\Media\Models\VehicleImage::count());
    }

    public function test_create_without_images_still_works(): void
    {
        $this->actingAs($this->vendorAdmin)->post(route('vendor.products.store'), [
            'category_id' => $this->category->id,
            'title' => 'No Photo Item',
            'description' => 'An item with no photos attached at all.',
            'price_zwl' => 1000, 'quantity' => 5,
        ])->assertRedirect();

        $this->assertDatabaseHas('products', ['title' => 'No Photo Item']);
        $this->assertSame(0, \App\Modules\Media\Models\ProductImage::count());
    }

    public function test_content_sniff_rejects_real_non_image(): void
    {
        // A genuine non-image with a forged .jpg name + image/jpeg client MIME.
        // Unlike UploadedFile::fake() (which reports its declared MIME), this real
        // file forces the server to sniff the bytes — and reject them.
        $product = \App\Modules\Products\Models\Product::create([
            'id' => (string) Str::uuid(), 'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id, 'title' => 'Host Product',
            'description' => 'x', 'price_zwl' => 1000, 'quantity' => 5, 'status' => 'pending',
        ]);

        $tmp = tempnam(sys_get_temp_dir(), 'evil');
        file_put_contents($tmp, "Not an image — plain text payload pretending to be a JPEG.");
        $forged = new UploadedFile($tmp, 'evil.jpg', 'image/jpeg', null, true);

        $this->actingAs($this->vendorAdmin)
            ->post(route('vendor.products.images.store', $product), ['image' => $forged])
            ->assertSessionHasErrors('image');

        $this->assertSame(0, \App\Modules\Media\Models\ProductImage::count());
        Queue::assertNotPushed(ImageProcessingJob::class);

        @unlink($tmp);
    }

    public function test_storage_uses_random_filename_and_safe_extension(): void
    {
        $this->actingAs($this->vendorAdmin)->post(route('vendor.products.store'), [
            'category_id' => $this->category->id,
            'title' => 'Filename Test Item',
            'description' => 'Checks the stored filename is randomised, not user-supplied.',
            'price_zwl' => 1000, 'quantity' => 5,
            'images' => [UploadedFile::fake()->image('../../etc/passwd.jpg', 400, 300)],
        ])->assertRedirect();

        $image = \App\Modules\Media\Models\ProductImage::first();
        $this->assertNotNull($image);
        $this->assertStringNotContainsString('passwd', $image->original_path);
        $this->assertStringNotContainsString('..', $image->original_path);
        $this->assertMatchesRegularExpression('/[0-9a-f-]{36}\.jpg$/', $image->original_path);
    }
}
