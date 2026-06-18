<?php

namespace App\Modules\Media\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Modules\Media\Exceptions\ImageUploadException;
use App\Modules\Media\Models\VehicleImage;
use App\Modules\Media\Services\ImageUploadService;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Verification\Exceptions\ListingLimitExceededException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VehicleImageController extends Controller
{
    public function __construct(private readonly ImageUploadService $uploadService) {}

    public function store(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);
        abort_unless($vehicle->vendor_id === $vendor->id, 403);

        $request->validate([
            'image'     => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'view_type' => ['nullable', 'string', 'in:front,side,back,interior,other'],
        ]);

        try {
            $this->uploadService->uploadForVehicleByVendor(
                $vendor,
                $vehicle,
                $request->file('image'),
                $request->input('view_type'),
            );
        } catch (ListingLimitExceededException | ImageUploadException $e) {
            return back()->withErrors(['image' => $e->getMessage()]);
        }

        return back()->with('status', 'Image uploaded and queued for processing.');
    }

    public function destroy(Request $request, Vehicle $vehicle, VehicleImage $image): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);
        abort_unless($vehicle->vendor_id === $vendor->id, 403);
        abort_unless($image->vehicle_id === $vehicle->id, 403);

        $image->delete();

        return back()->with('status', 'Image removed.');
    }

    public function reorder(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);
        abort_unless($vehicle->vendor_id === $vendor->id, 403);

        $request->validate(['order' => ['required', 'array']]);

        foreach ($request->input('order') as $position => $imageId) {
            VehicleImage::where('id', $imageId)
                ->where('vehicle_id', $vehicle->id)
                ->update(['display_order' => $position]);
        }

        return back()->with('status', 'Image order saved.');
    }
}
