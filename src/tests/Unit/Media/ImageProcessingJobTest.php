<?php

namespace Tests\Unit\Media;

use App\Jobs\Media\ImageProcessingJob;
use App\Modules\Media\Models\VehicleImage;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImageProcessingJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_skips_gracefully_when_image_not_found(): void
    {
        Storage::fake('public');

        $job = new ImageProcessingJob((string) Str::uuid(), 'vehicle');
        $job->handle();  // must not throw

        $this->assertTrue(true);
    }

    public function test_job_creates_medium_and_thumb_variants(): void
    {
        if (!class_exists(\Intervention\Image\ImageManager::class)) {
            $this->markTestSkipped('intervention/image not installed');
        }

        Storage::fake('public');

        $make   = VehicleMake::create(['name' => 'Toyota', 'slug' => 'toyota', 'sort_order' => 0]);
        $vModel = VehicleModel::create(['make_id' => $make->id, 'name' => 'Corolla', 'slug' => 'corolla']);

        $user    = \App\Models\User::factory()->create();
        $vehicle = Vehicle::create([
            'vendor_id'    => null,
            'user_id'      => $user->id,
            'make_id'      => $make->id,
            'model_id'     => $vModel->id,
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

        // Create a 100×100 JPEG via GD and store it on the fake disk
        $gdImage   = imagecreatetruecolor(100, 100);
        ob_start();
        imagejpeg($gdImage);
        $jpegBytes = ob_get_clean();

        $imagePath = 'vehicles/' . $vehicle->id . '/original.jpg';
        Storage::disk('public')->put($imagePath, $jpegBytes);

        $image = VehicleImage::create([
            'vehicle_id'    => $vehicle->id,
            'disk'          => 'public',
            'original_path' => $imagePath,
            'display_order' => 0,
        ]);

        (new ImageProcessingJob($image->id, 'vehicle'))->handle();

        $image->refresh();

        $this->assertNotNull($image->medium_path);
        $this->assertNotNull($image->thumb_path);
        $this->assertNotNull($image->processed_at);
        Storage::disk('public')->assertExists($image->medium_path);
        Storage::disk('public')->assertExists($image->thumb_path);
    }
}
