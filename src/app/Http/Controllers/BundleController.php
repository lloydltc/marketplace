<?php

namespace App\Http\Controllers;

use App\Modules\Cart\Services\CartService;
use App\Modules\Parts\Models\PartBundle;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * PM6: public service-kit bundle page + "add kit to cart". Adding expands the
 * bundle into its component cart lines, so the existing cart/checkout/commission/
 * wallet engine handles a kit purchase with no changes.
 */
class BundleController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function show(PartBundle $bundle): View
    {
        abort_unless($bundle->isActive(), 404);

        $bundle->load(['items.product.vendor', 'items.product.part']);

        return view('bundles.show', compact('bundle'));
    }

    public function addToCart(PartBundle $bundle): RedirectResponse
    {
        abort_unless($bundle->isActive(), 404);

        $bundle->load('items.product.vendor');

        if (! $bundle->isInStock()) {
            return back()->withErrors(['cart' => 'One or more items in this kit are out of stock.']);
        }

        foreach ($bundle->items as $item) {
            $product = $item->product;

            // Mirror the cart's transactability gate (unverified sellers can't sell yet).
            if ($product === null || ($product->vendor !== null && ! $product->vendor->canTransact())) {
                return back()->withErrors(['cart' => 'This kit cannot be purchased right now.']);
            }

            $this->cart->add($product->id, $item->qty);
        }

        return redirect()->route('cart.index')->with('status', 'Service kit added to your cart.');
    }
}
