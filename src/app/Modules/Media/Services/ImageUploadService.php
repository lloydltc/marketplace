<?php

namespace App\Modules\Media\Services;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Media\Models\ProductImage;
use App\Modules\Media\Models\VehicleImage;
use App\Modules\Products\Models\Product;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Verification\Services\TierService;
use App\Jobs\Media\ImageProcessingJob;
use Illuminate\Http\UploadedFile;

class ImageUploadService
{
    public const ACCEPTED_MIMES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    public const MAX_FILE_SIZE  = 10 * 1024 * 1024; // 10 MB in bytes
    public const MAX_DIMENSION  = 6000;             // px — reject decompression-bomb dimensions

    public function __construct(
        private readonly StorageService $storage,
        private readonly TierService $tierService,
    ) {}

    public function uploadForProduct(Vendor $vendor, Product $product, UploadedFile $file): ProductImage
    {
        $this->validateFile($file);

        $currentCount = $product->images()->count();
        $this->tierService->assertCanUploadProductImageForVendor($vendor, $currentCount);

        $path  = $this->storage->store($file, 'products/' . $product->id, self::extensionForMime($file->getMimeType()));
        $order = $currentCount;

        $image = ProductImage::create([
            'product_id'    => $product->id,
            'disk'          => $this->storage->disk(),
            'original_path' => $path,
            'file_size'     => $file->getSize(),
            'display_order' => $order,
        ]);

        ImageProcessingJob::dispatch($image->id, 'product');

        return $image;
    }

    public function uploadForVehicleByVendor(Vendor $vendor, Vehicle $vehicle, UploadedFile $file, ?string $viewType = null): VehicleImage
    {
        $this->validateFile($file);

        $currentCount = $vehicle->images()->count();
        $this->tierService->assertCanUploadVehicleImageForVendor($vendor, $currentCount);

        return $this->storeVehicleImage($vehicle, $file, $viewType, $currentCount);
    }

    public function uploadForVehicleBySeller(User $user, Vehicle $vehicle, UploadedFile $file, ?string $viewType = null): VehicleImage
    {
        $this->validateFile($file);

        $currentCount = $vehicle->images()->count();
        $this->tierService->assertCanUploadVehicleImageForSeller($user, $currentCount);

        return $this->storeVehicleImage($vehicle, $file, $viewType, $currentCount);
    }

    private function storeVehicleImage(Vehicle $vehicle, UploadedFile $file, ?string $viewType, int $order): VehicleImage
    {
        $path = $this->storage->store($file, 'vehicles/' . $vehicle->id, self::extensionForMime($file->getMimeType()));

        $image = VehicleImage::create([
            'vehicle_id'    => $vehicle->id,
            'disk'          => $this->storage->disk(),
            'original_path' => $path,
            'view_type'     => $viewType,
            'file_size'     => $file->getSize(),
            'display_order' => $order,
        ]);

        ImageProcessingJob::dispatch($image->id, 'vehicle');

        return $image;
    }

    /**
     * Production-secure validation: never trust the client filename or the
     * client-declared MIME. We (1) sniff the real MIME from the file bytes
     * (finfo), (2) confirm it is a true raster image via getimagesize(), and
     * (3) bound size + pixel dimensions (decompression-bomb guard). The original
     * bytes are additionally re-encoded/normalised downstream
     * (ImageProcessingJob) to strip any embedded EXIF/payload.
     */
    private function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \App\Modules\Media\Exceptions\ImageUploadException(
                'Image must be smaller than 10 MB.'
            );
        }

        // Content sniff — getMimeType() reads the bytes (finfo), not the header.
        if (! in_array($file->getMimeType(), self::ACCEPTED_MIMES, true)) {
            throw new \App\Modules\Media\Exceptions\ImageUploadException(
                'Only JPEG, PNG, and WebP images are accepted.'
            );
        }

        // Confirm the bytes actually decode as an image and the sniffed type
        // matches an allowed image type (rejects polyglots / renamed files).
        $info = @getimagesize($file->getRealPath());
        if ($info === false || ! in_array($info['mime'] ?? '', self::ACCEPTED_MIMES, true)) {
            throw new \App\Modules\Media\Exceptions\ImageUploadException(
                'That file is not a valid image.'
            );
        }

        if ($info[0] > self::MAX_DIMENSION || $info[1] > self::MAX_DIMENSION) {
            throw new \App\Modules\Media\Exceptions\ImageUploadException(
                'Image dimensions are too large (max ' . self::MAX_DIMENSION . 'px per side).'
            );
        }
    }

    /** Safe storage extension derived from the sniffed MIME — never the client name. */
    public static function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg', // jpeg/jpg
        };
    }
}
