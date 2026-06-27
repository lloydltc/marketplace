<?php

namespace App\Modules\Parts\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Modules\Parts\Models\PartBundle;
use App\Modules\Products\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PM6: vendor builds service kits from their own offerings.
 */
class BundleController extends Controller
{
    public function index(Request $request): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        $bundles = PartBundle::where('vendor_id', $vendor->id)
            ->withCount('items')->latest()->paginate(20);

        return view('vendor.bundles.index', compact('bundles'));
    }

    public function create(Request $request): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        $offerings = Product::where('vendor_id', $vendor->id)->where('status', 'active')->orderBy('title')->get();

        return view('vendor.bundles.create', compact('offerings'));
    }

    public function store(Request $request): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'description'    => ['nullable', 'string', 'max:2000'],
            'price_usd'      => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'items'          => ['required', 'array', 'min:1'],
            'items.*'        => ['nullable', 'integer', 'min:0', 'max:999'], // product_id => qty
        ]);

        // Only the vendor's own active offerings may be components.
        $ownIds = Product::where('vendor_id', $vendor->id)->where('status', 'active')->pluck('id')->all();
        $items = collect($validated['items'])
            ->filter(fn ($qty, $productId) => $qty > 0 && in_array($productId, $ownIds, true));

        if ($items->isEmpty()) {
            return back()->withErrors(['items' => 'Select at least one of your offerings for the kit.'])->withInput();
        }

        $bundle = PartBundle::create([
            'vendor_id'   => $vendor->id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price_usd'   => $validated['price_usd'] ?? null,
        ]);

        foreach ($items as $productId => $qty) {
            $bundle->items()->create(['product_id' => $productId, 'qty' => (int) $qty]);
        }

        return redirect()->route('vendor.bundles.index')->with('status', 'Service kit created.');
    }

    public function destroy(Request $request, PartBundle $bundle): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_unless($vendor !== null && $bundle->vendor_id === $vendor->id, 403);

        $bundle->delete();

        return back()->with('status', 'Service kit removed.');
    }
}
