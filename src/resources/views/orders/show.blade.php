<x-layouts.app>
    <x-slot:title>Order {{ $order->order_number }}</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <a href="{{ route('orders.index') }}" class="text-sm text-[#3DB8E8] hover:underline">← My orders</a>

        <div class="flex items-center justify-between mt-3 mb-6">
            <h1 class="text-2xl font-semibold text-neutral-900 font-mono">{{ $order->order_number }}</h1>
            <x-order-status :status="$order->status" />
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif
        @error('order')
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $message }}</div>
        @enderror

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 mb-4">
            <div class="text-xs text-neutral-400 uppercase tracking-wide mb-1">Sold by</div>
            <div class="font-medium text-neutral-800 mb-3">{{ $order->vendor?->name }}</div>
            <div class="text-sm text-neutral-500">
                {{ $order->fulfilment_track === 'fbs' ? 'Fulfilled by Salma' : 'Vendor-fulfilled' }} ·
                {{ $order->payment_method === 'cod' ? 'Cash on delivery' : 'Prepaid' }}
            </div>

            <ul class="text-sm text-neutral-700 space-y-1 my-4">
                @foreach ($order->items as $item)
                    <li class="flex justify-between">
                        <span>{{ $item->quantity }} × {{ $item->title }}</span>
                        <span class="tabular-nums">ZWL {{ number_format($item->line_total, 2) }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="border-t border-neutral-100 pt-3 text-sm space-y-1">
                <div class="flex justify-between text-neutral-500"><span>Subtotal</span><span class="tabular-nums">ZWL {{ number_format($order->subtotal, 2) }}</span></div>
                <div class="flex justify-between text-neutral-500"><span>Delivery</span><span class="tabular-nums">ZWL {{ number_format($order->delivery_fee, 2) }}</span></div>
                <div class="flex justify-between font-semibold text-neutral-900"><span>Total</span><span class="tabular-nums">ZWL {{ number_format($order->total, 2) }}</span></div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('orders.invoice', $order) }}" class="text-sm font-medium text-[#3DB8E8] hover:underline">View invoice</a>

            @if ($order->isAwaitingPayment() && $order->isPrepaid())
                <a href="{{ route('checkout.complete') }}" class="text-sm font-medium text-[#F0A820] hover:underline">Complete payment</a>
            @endif

            @if ($order->fulfilment_track === 'vendor' && $order->status === 'delivered')
                <form method="POST" action="{{ route('orders.confirm', $order) }}">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-green-600 hover:underline">Confirm receipt</button>
                </form>
            @endif

            @if ($order->canTransitionTo('cancelled'))
                <form method="POST" action="{{ route('orders.cancel', $order) }}" class="ml-auto"
                      onsubmit="return confirm('Cancel this order?')">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-red-500 hover:underline">Cancel order</button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
