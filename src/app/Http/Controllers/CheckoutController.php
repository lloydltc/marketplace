<?php

namespace App\Http\Controllers;

use App\Modules\Cart\Services\CartService;
use App\Modules\Checkout\Exceptions\CheckoutValidationException;
use App\Modules\Checkout\Services\CheckoutService;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    private const SESSION_INTENT = 'checkout.intent';
    private const SESSION_ORDERS = 'checkout.orders';

    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutService $checkout,
        private readonly OrderService $orders
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index')->with('status', 'Your cart is empty.');
        }

        $groups  = $this->cart->groups();
        $choices = [];
        foreach ($groups as $group) {
            $choices[$group->key()] = $this->checkout->fulfilmentChoices($group);
        }

        $user = $request->user();

        return view('checkout.index', [
            'groups'   => $groups,
            'choices'  => $choices,
            'subtotal' => $this->cart->subtotal(),
            'prefill'  => ['full_name' => $user->name ?? '', 'email' => $user->email ?? ''],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index')->with('status', 'Your cart is empty.');
        }

        $customer   = $this->validateCustomer($request);
        $selections = $this->selectionsFrom($request);

        try {
            // Validate the selections against the COD/fulfilment matrix now.
            $this->checkout->buildSummary($selections, $customer);
        } catch (CheckoutValidationException $e) {
            return back()->withErrors(['checkout' => $e->getMessage()])->withInput();
        }

        $request->session()->put(self::SESSION_INTENT, compact('selections', 'customer'));

        return redirect()->route('checkout.payment');
    }

    public function payment(Request $request): View|RedirectResponse
    {
        $intent = $request->session()->get(self::SESSION_INTENT);

        if (! $intent || $this->cart->isEmpty()) {
            return redirect()->route('checkout.show');
        }

        try {
            $summary = $this->checkout->buildSummary($intent['selections'], $intent['customer']);
        } catch (CheckoutValidationException) {
            return redirect()->route('checkout.show')->withErrors(['checkout' => 'Please review your order.']);
        }

        return view('checkout.payment', compact('summary'));
    }

    /**
     * Create the orders (one per vendor group) and snapshot commission.
     */
    public function place(Request $request): RedirectResponse
    {
        $intent = $request->session()->get(self::SESSION_INTENT);

        if (! $intent || $this->cart->isEmpty()) {
            return redirect()->route('checkout.show');
        }

        try {
            // Re-validate at the last moment before persisting.
            $this->checkout->buildSummary($intent['selections'], $intent['customer']);
        } catch (CheckoutValidationException $e) {
            return redirect()->route('checkout.show')->withErrors(['checkout' => $e->getMessage()]);
        }

        $orders = $this->orders->createFromCart(
            $this->cart->groups(),
            $intent['selections'],
            $intent['customer'],
            $request->user()?->id,
        );

        $this->cart->clear();
        $request->session()->forget(self::SESSION_INTENT);
        $request->session()->put(self::SESSION_ORDERS, array_map(fn (Order $o) => $o->id, $orders));

        return redirect()->route('checkout.complete');
    }

    public function complete(Request $request): View|RedirectResponse
    {
        $orderIds = $request->session()->get(self::SESSION_ORDERS, []);

        if ($orderIds === []) {
            return redirect()->route('home');
        }

        $orders = Order::with('items')->whereIn('id', $orderIds)->get();

        return view('checkout.complete', compact('orders'));
    }

    /**
     * @return array<string, string>
     */
    private function validateCustomer(Request $request): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email'     => ['required', 'email', 'max:160'],
            'phone'     => ['required', 'string', 'max:30'],
            'address'   => ['required', 'string', 'max:255'],
            'city'      => ['required', 'string', 'max:80'],
        ]);
    }

    /**
     * @return array<string, array{fulfilment: ?string, payment: string}>
     */
    private function selectionsFrom(Request $request): array
    {
        $fulfilment = (array) $request->input('fulfilment', []);
        $payment    = (array) $request->input('payment', []);

        $selections = [];
        foreach (array_unique([...array_keys($fulfilment), ...array_keys($payment)]) as $key) {
            $selections[$key] = [
                'fulfilment' => $fulfilment[$key] ?? null,
                'payment'    => $payment[$key] ?? 'prepaid',
            ];
        }

        return $selections;
    }
}
