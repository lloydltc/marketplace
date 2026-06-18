<x-layouts.app>
    <x-slot:title>Payment</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-2xl font-semibold text-neutral-900 mb-1">Review & pay</h1>
        <p class="text-sm text-neutral-500 mb-6">
            Delivering to {{ $summary['customer']['full_name'] }}, {{ $summary['customer']['address'] }}, {{ $summary['customer']['city'] }}.
        </p>

        <div class="space-y-4">
            @foreach ($summary['groups'] as $group)
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-medium text-neutral-900">{{ $group['vendorName'] }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $group['payment'] === 'cod' ? 'bg-green-50 text-green-700' : 'bg-blue-50 text-blue-600' }}">
                            {{ $group['paymentLabel'] }}
                        </span>
                    </div>
                    <div class="text-xs text-neutral-500 mb-3">{{ $group['fulfilmentLabel'] }}</div>

                    <ul class="text-sm text-neutral-600 space-y-1 mb-3">
                        @foreach ($group['lines'] as $line)
                            <li class="flex justify-between">
                                <span>{{ $line['quantity'] }} × {{ $line['title'] }}</span>
                                <span class="tabular-nums">ZWL {{ number_format($line['lineTotal'], 2) }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="border-t border-neutral-100 pt-2 text-sm space-y-1">
                        <div class="flex justify-between text-neutral-500">
                            <span>Subtotal</span><span class="tabular-nums">ZWL {{ number_format($group['subtotal'], 2) }}</span>
                        </div>
                        <div class="flex justify-between text-neutral-500">
                            <span>Delivery</span>
                            <span class="tabular-nums">{{ $group['deliveryFee'] > 0 ? 'ZWL ' . number_format($group['deliveryFee'], 2) : 'Vendor-arranged' }}</span>
                        </div>
                        <div class="flex justify-between font-semibold text-neutral-900">
                            <span>Order total</span><span class="tabular-nums">ZWL {{ number_format($group['total'], 2) }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Grand total --}}
        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 mt-4">
            <div class="flex justify-between text-sm text-neutral-500 mb-1">
                <span>Items</span><span class="tabular-nums">ZWL {{ number_format($summary['subtotal'], 2) }}</span>
            </div>
            <div class="flex justify-between text-sm text-neutral-500 mb-1">
                <span>Delivery</span><span class="tabular-nums">ZWL {{ number_format($summary['delivery'], 2) }}</span>
            </div>
            <div class="flex justify-between text-base font-semibold text-neutral-900 border-t border-neutral-100 pt-2">
                <span>Total</span><span class="tabular-nums">ZWL {{ number_format($summary['total'], 2) }}</span>
            </div>
            <p class="text-xs text-neutral-400 mt-1">
                {{ count($summary['groups']) }} {{ Str::plural('order', count($summary['groups'])) }} will be created — one per vendor.
            </p>
        </div>

        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-800">
            Placing your order creates one order per vendor. Prepaid orders are paid securely via Pesepay;
            cash-on-delivery orders are confirmed at delivery.
        </div>

        <form method="POST" action="{{ route('checkout.place') }}" class="mt-5">
            @csrf
            <button type="submit"
                    class="w-full bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold py-2.5 rounded-lg text-sm transition-colors">
                Place order
            </button>
        </form>
        <a href="{{ route('checkout.show') }}" class="block text-center text-sm text-neutral-500 hover:text-neutral-700 mt-3">← Edit order</a>
    </div>
</x-layouts.app>
