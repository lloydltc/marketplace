<?php

namespace App\Jobs\Media;

use App\Modules\Media\Models\ProductImage;
use App\Modules\Media\Models\VehicleImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly string $imageId,
        public readonly string $imageType,  // 'product' or 'vehicle'
    ) {}

    public function handle(): void
    {
        $image = match ($this->imageType) {
            'product' => ProductImage::find($this->imageId),
            'vehicle' => VehicleImage::find($this->imageId),
            default   => null,
        };

        if (!$image) {
            Log::warning('ImageProcessingJob: image not found', ['id' => $this->imageId, 'type' => $this->imageType]);
            return;
        }

        $disk     = $image->disk;
        $contents = Storage::disk($disk)->get($image->original_path);

        if (!$contents) {
            Log::error('ImageProcessingJob: original not found on disk', ['path' => $image->original_path]);
            return;
        }

        $manager  = new ImageManager(new Driver());
        $img      = $manager->decodeBinary($contents);

        $basePath = pathinfo($image->original_path, PATHINFO_DIRNAME);
        $stem     = pathinfo($image->original_path, PATHINFO_FILENAME);
        $ext      = strtolower(pathinfo($image->original_path, PATHINFO_EXTENSION));

        // Sanitise the stored original: re-encode it (in its own format) so any
        // embedded EXIF/ICC/metadata or non-image payload is stripped from the
        // bytes we actually serve publicly. The client upload is never served raw.
        $sanitised = match ($ext) {
            'png'  => $manager->decodeBinary($contents)->encode(new PngEncoder()),
            'webp' => $manager->decodeBinary($contents)->encode(new WebpEncoder(90)),
            default => $manager->decodeBinary($contents)->encode(new JpegEncoder(90)),
        };
        Storage::disk($disk)->put($image->original_path, $sanitised->toString());

        // Medium: scale down to fit within 800×600 preserving aspect ratio
        $medium     = $manager->decodeBinary($contents);
        $medium->scaleDown(800, 600);
        $mediumPath = $basePath . '/' . $stem . '_medium.jpg';
        Storage::disk($disk)->put($mediumPath, $medium->encode(new JpegEncoder(80))->toString());

        // Thumbnail: cover-crop to 300×200
        $thumb     = $manager->decodeBinary($contents);
        $thumb->cover(300, 200);
        $thumbPath = $basePath . '/' . $stem . '_thumb.jpg';
        Storage::disk($disk)->put($thumbPath, $thumb->encode(new JpegEncoder(75))->toString());

        $image->update([
            'medium_path'  => $mediumPath,
            'thumb_path'   => $thumbPath,
            'width'        => $img->width(),
            'height'       => $img->height(),
            'processed_at' => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ImageProcessingJob failed', [
            'id'    => $this->imageId,
            'type'  => $this->imageType,
            'error' => $e->getMessage(),
        ]);
    }
}
