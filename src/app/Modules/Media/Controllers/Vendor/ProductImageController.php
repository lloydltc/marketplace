<?php

namespace App\Modules\Media\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Modules\Media\Exceptions\ImageUploadException;
use App\Modules\Media\Models\ProductImage;
use App\Modules\Media\Services\ImageUploadService;
use App\Modules\Products\Models\Product;
use App\Modules\Verification\Exceptions\ListingLimitExceededException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    public function __construct(private readonly ImageUploadService $uploadService) {}

    public function store(Request $request, Product $product): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);
        abort_unless($product->vendor_id === $vendor->id, 403);

        $request->validate([
            'image' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ]);

        try {
            $this->uploadService->uploadForProduct($vendor, $product, $request->file('image'));
        } catch (ListingLimitExceededException | ImageUploadException $e) {
            return back()->withErrors(['image' => $e->getMessage()]);
        }

        return back()->with('status', 'Image uploaded and queued for processing.');
    }

    public function destroy(Request $request, Product $product, ProductImage $image): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);
        abort_unless($product->vendor_id === $vendor->id, 403);
        abort_unless($image->product_id === $product->id, 403);

        $image->delete();

        return back()->with('status', 'Image removed.');
    }

    public function reorder(Request $request, Product $product): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);
        abort_unless($product->vendor_id === $vendor->id, 403);

        $request->validate(['order' => ['required', 'array']]);

        foreach ($request->input('order') as $position => $imageId) {
            ProductImage::where('id', $imageId)
                ->where('product_id', $product->id)
                ->update(['display_order' => $position]);
        }

        return back()->with('status', 'Image order saved.');
    }
}
