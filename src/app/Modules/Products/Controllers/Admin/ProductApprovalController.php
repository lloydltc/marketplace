<?php

namespace App\Modules\Products\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Requests\Admin\ApproveProductRequest;
use App\Modules\Products\Requests\Admin\RejectProductRequest;
use App\Modules\Products\Services\ProductService;
use Illuminate\Http\RedirectResponse;

class ProductApprovalController extends Controller
{
    public function __construct(
        private readonly ProductService $service
    ) {}

    public function approve(ApproveProductRequest $request, Product $product): RedirectResponse
    {
        $this->service->approve($product, $request->user());

        return redirect()
            ->route('admin.products.show', $product)
            ->with('status', 'Product approved and is now live.');
    }

    public function reject(RejectProductRequest $request, Product $product): RedirectResponse
    {
        $this->service->reject($product, $request->user(), $request->validated('reason'));

        return redirect()
            ->route('admin.products.show', $product)
            ->with('status', 'Product rejected. The vendor has been notified.');
    }
}
