<?php

namespace App\Modules\Products\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductFitment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * H10: vendor manages the vehicle-compatibility rules on one of their parts.
 */
class ProductFitmentController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeProduct($request, $product);

        $validated = $request->validate([
            'make_id'   => ['nullable', 'uuid', 'exists:vehicle_makes,id'],
            'model_id'  => ['nullable', 'uuid', 'exists:vehicle_models,id'],
            'year_from' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'year_to'   => ['nullable', 'integer', 'min:1900', 'max:2100', 'gte:year_from'],
        ]);

        // A model without its make is ambiguous; require the make when a model is set.
        if (! empty($validated['model_id']) && empty($validated['make_id'])) {
            return back()->withErrors(['make_id' => 'Select the make for this model.']);
        }

        // At least one axis must be specified — an all-null rule "fits everything".
        if (empty(array_filter($validated))) {
            return back()->withErrors(['make_id' => 'Specify at least a make or a year range.']);
        }

        $product->fitments()->create($validated);

        return back()->with('status', 'Compatibility rule added.');
    }

    public function destroy(Request $request, Product $product, ProductFitment $fitment): RedirectResponse
    {
        $this->authorizeProduct($request, $product);
        abort_unless($fitment->product_id === $product->id, 404);

        $fitment->delete();

        return back()->with('status', 'Compatibility rule removed.');
    }

    private function authorizeProduct(Request $request, Product $product): void
    {
        $vendor = $request->attributes->get('vendor');
        abort_unless($vendor !== null && $product->vendor_id === $vendor->id, 403);
    }
}
