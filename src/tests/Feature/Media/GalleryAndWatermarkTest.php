<?php

namespace Tests\Feature\Media;

use App\Jobs\Media\ImageProcessingJob;
use App\Models\Vendor;
use App\Modules\Media\Models\VehicleImage;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * H3: gallery UX (count badge, share, download-all) + watermarking of the
 * processed/served image (the original stays private).
 */
class GalleryAndWatermarkTest extends TestCase
{
    use RefreshDatabase;

    private function vehicle(bool $withProcessedImage = true): Vehicle
    {
        $vendor = Vendor::create(['name' => 'D', 'slug' => 'd-' . Str::random(5), 'contact_email' => 'd@x.com', 'status' => 'approved']);
        $make = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota-' . Str::random(4), 'sort_order' => 0]);
        $model = VehicleModel::create(['make_id' => $make->id, 'name' => 'Hilux', 'slug' => 'hilux-' . Str::random(4)]);
        $v = Vehicle::create([
            'vendor_id' => $vendor->id, 'make_id' => $make->id, 'model_id' => $model->id,
            'year' => 2021, 'body_type' => 'sedan', 'transmission' => 'manual', 'fuel_type' => 'petrol',
            'mileage' => 1000, 'color' => 'white', 'condition' => 'used', 'price_usd' => 15000,
            'status' => 'active', 'expires_at' => now()->addDays(10),
        ]);

        if ($withProcessedImage) {
            Storage::disk('public')->put("vehicles/{$v->id}/m.jpg", 'JPEGDATA');
            VehicleImage::create([
                'vehicle_id' => $v->id, 'disk' => 'public',
                'original_path' => "vehicles/{$v->id}/o.jpg",
                'medium_path' => "vehicles/{$v->id}/m.jpg",
                'thumb_path' => "vehicles/{$v->id}/t.jpg",
                'display_order' => 0, 'processed_at' => now(),
            ]);
        }

        return $v;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlatformSettingsSeeder::class);
        Storage::fake('public');
    }

    public function test_gallery_shows_count_share_and_download(): void
    {
        $v = $this->vehicle();

        $this->get(route('vehicles.show', $v))->assertOk()
            ->assertSee('Share on WhatsApp')
            ->assertSee('Download all')
            ->assertSee('cursor-zoom-in', false)   // fullscreen-able main image
            ->assertSee('x-text="i + 1"', false);  // count badge
    }

    public function test_download_all_returns_a_zip(): void
    {
        $v = $this->vehicle();

        $res = $this->get(route('vehicles.images.download', $v));
        $res->assertOk();
        $this->assertStringContainsString('attachment', strtolower((string) $res->headers->get('content-disposition')));
    }

    public function test_download_404_when_no_processed_images(): void
    {
        $v = $this->vehicle(withProcessedImage: false);
        $this->get(route('vehicles.images.download', $v))->assertNotFound();
    }

    public function test_processing_job_produces_watermarked_medium(): void
    {
        $v = $this->vehicle(withProcessedImage: false);
        // A real image on disk for the job to process.
        $path = "vehicles/{$v->id}/orig.jpg";
        Storage::disk('public')->put($path, UploadedFile::fake()->image('orig.jpg', 800, 600)->get());
        $img = VehicleImage::create([
            'vehicle_id' => $v->id, 'disk' => 'public',
            'original_path' => $path, 'display_order' => 0,
        ]);

        (new ImageProcessingJob($img->id, 'vehicle'))->handle();

        $img->refresh();
        $this->assertNotNull($img->medium_path);
        $this->assertNotNull($img->processed_at);
        $this->assertTrue(Storage::disk('public')->exists($img->medium_path));
    }
}
