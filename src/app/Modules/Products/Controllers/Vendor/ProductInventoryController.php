<?php

namespace App\Modules\Products\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * PM2: vendor adjusts on-hand stock for one of their offerings. Every change is
 * recorded as an auditable InventoryMovement via InventoryService.
 */
class ProductInventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function adjust(Request $request, Product $product): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_unless($vendor !== null && $product->vendor_id === $vendor->id, 403);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'note'     => ['nullable', 'string', 'max:255'],
        ]);

        $this->inventory->adjustTo(
            $product,
            $validated['quantity'],
            $validated['note'] ?? 'Manual adjustment',
            $request->user(),
        );

        return back()->with('status', 'Stock updated to ' . $validated['quantity'] . '.');
    }
}
