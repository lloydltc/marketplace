<?php

namespace App\Modules\Products\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Modules\Categories\Repositories\CategoryRepositoryInterface;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Repositories\ProductRepositoryInterface;
use App\Modules\Products\Requests\Vendor\StoreProductRequest;
use App\Modules\Products\Requests\Vendor\UpdateProductRequest;
use App\Modules\Media\Exceptions\ImageUploadException;
use App\Modules\Media\Services\ImageUploadService;
use App\Modules\Products\Services\InventoryService;
use App\Modules\Products\Services\ProductService;
use App\Modules\Verification\Exceptions\ListingLimitExceededException;
use App\Modules\Verification\Services\TierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
        private readonly ProductService $service,
        private readonly InventoryService $inventoryService,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly TierService $tierService,
        private readonly ImageUploadService $imageUploadService,
    ) {}

    public function index(Request $request): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        $products = $this->repository->paginateForVendor($vendor->id, [
            'status' => $request->get('status'),
            'search' => $request->get('search'),
        ]);

        return view('vendor.products.index', compact('products', 'vendor'));
    }

    public function create(Request $request): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);
        abort_unless($vendor->isApproved(), 403, 'Your vendor account must be approved before listing products.');

        $categories = $this->categoryRepository->allWithChildren();

        return view('vendor.products.create', compact('vendor', 'categories'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        $product = $this->service->createForVendor($vendor, $request->validated());

        $imageNote = '';
        foreach ($request->file('images', []) as $file) {
            try {
                $this->imageUploadService->uploadForProduct($vendor, $product, $file);
            } catch (ListingLimitExceededException | ImageUploadException $e) {
                $imageNote = ' Some images were not added: ' . $e->getMessage();
                break;
            }
        }

        return redirect()
            ->route('vendor.products.show', $product)
            ->with('status', 'Product submitted for review.' . $imageNote);
    }

    public function show(Request $request, Product $product): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);
        abort_unless($product->vendor_id === $vendor->id, 403);

        return view('vendor.products.show', compact('product', 'vendor'));
    }

    public function edit(Request $request, Product $product): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);
        abort_unless($product->vendor_id === $vendor->id, 403);
        abort_unless($product->canBeEditedByVendor(), 403, 'Active products cannot be edited. Deactivate first.');

        $categories = $this->categoryRepository->allWithChildren();
        $images     = $product->images()->orderBy('display_order')->get();
        $imageLimit = $this->tierService->vendorProductImageLimit($vendor);

        return view('vendor.products.edit', compact('product', 'vendor', 'categories', 'images', 'imageLimit'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);
        abort_unless($product->vendor_id === $vendor->id, 403);

        $this->service->update($product, $request->validated());

        return redirect()
            ->route('vendor.products.show', $product)
            ->with('status', 'Product updated and resubmitted for review.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);
        abort_unless($product->vendor_id === $vendor->id, 403);

        $this->service->delete($product);

        return redirect()
            ->route('vendor.products.index')
            ->with('status', 'Product deleted.');
    }
}
