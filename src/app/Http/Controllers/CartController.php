<?php

namespace App\Http\Controllers;

use App\Modules\Cart\Services\CartService;
use App\Modules\Products\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index(): View
    {
        return view('cart.index', [
            'groups'   => $this->cart->groups(),
            'subtotal' => $this->cart->subtotal(),
            'total'    => $this->cart->total(),
            'count'    => $this->cart->count(),
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'string'],
            'quantity'   => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::query()->active()->with('vendor')->find($validated['product_id']);

        if ($product === null || ! $product->isInStock()) {
            return back()->withErrors(['cart' => 'That product is no longer available.']);
        }

        // Display-only gating: unverified/pending sellers' items can't be bought
        // until approved (remediation R4; configurable via sellers.unverified_can_transact).
        if ($product->vendor !== null && ! $product->vendor->canTransact()) {
            return back()->withErrors(['cart' => 'This seller is still being verified — their items can be viewed but not purchased yet.']);
        }

        $this->cart->add($product->id, $validated['quantity'] ?? 1);

        return back()->with('status', 'Added to cart.');
    }

    public function update(Request $request, string $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $this->cart->update($product, $validated['quantity']);

        return back()->with('status', 'Cart updated.');
    }

    public function remove(string $product): RedirectResponse
    {
        $this->cart->remove($product);

        return back()->with('status', 'Item removed.');
    }

    public function clear(): RedirectResponse
    {
        $this->cart->clear();

        return back()->with('status', 'Cart cleared.');
    }
}
