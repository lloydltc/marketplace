<x-layouts.app>
    <x-slot:title>Order {{ $order->order_number }}</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <a href="{{ route('vendor.orders.index') }}" class="text-sm text-[#3DB8E8] hover:underline">← All orders</a>

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
            <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                <div>
                    <div class="text-xs text-neutral-400 uppercase tracking-wide">Buyer</div>
                    <div class="text-neutral-800">{{ $order->buyer_name }}</div>
                    <div class="text-neutral-500">{{ $order->buyer_phone }}</div>
                    <div class="text-neutral-500">{{ $order->buyer_address }}, {{ $order->buyer_city }}</div>
                </div>
                <div>
                    <div class="text-xs text-neutral-400 uppercase tracking-wide">Fulfilment</div>
                    <div class="text-neutral-800">{{ $order->fulfilment_track === 'fbs' ? 'Fulfilled by Salma' : 'Vendor-fulfilled' }}</div>
                    <div class="text-neutral-500">{{ $order->payment_method === 'cod' ? 'Cash on delivery' : 'Prepaid' }}</div>
                </div>
            </div>

            <ul class="text-sm text-neutral-700 space-y-1 border-t border-neutral-100 pt-4">
                @foreach ($order->items as $item)
                    <li class="flex justify-between">
                        <span>{{ $item->quantity }} × {{ $item->title }}</span>
                        <span class="tabular-nums">ZWL {{ number_format($item->line_total, 2) }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="border-t border-neutral-100 mt-3 pt-3 text-sm space-y-1">
                <div class="flex justify-between text-neutral-500"><span>Net to you (after {{ number_format($order->commission_rate_applied, 2) }}% commission)</span><span class="tabular-nums font-medium text-neutral-800">ZWL {{ number_format($order->net_to_vendor, 2) }}</span></div>
                <div class="flex justify-between font-semibold text-neutral-900"><span>Order total</span><span class="tabular-nums">ZWL {{ number_format($order->total, 2) }}</span></div>
            </div>
        </div>

        {{-- Fulfilment actions --}}
        @php $next = $order->allowedTransitions(); @endphp
        @if (! empty($next))
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5">
                <div class="text-xs font-medium text-neutral-500 uppercase tracking-wide mb-3">Update status</div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($next as $to)
                        <form method="POST" action="{{ route('vendor.orders.transition', $order) }}">
                            @csrf
                            <input type="hidden" name="to" value="{{ $to }}">
                            <button type="submit"
                                    class="{{ $to === 'cancelled' ? 'border border-red-200 text-red-600 hover:bg-red-50' : 'bg-[#1A1A24] hover:bg-[#080810] text-white' }} text-sm font-medium px-3 py-1.5 rounded-lg transition-colors">
                                {{ ucfirst(str_replace('_', ' ', $to)) }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @else
            <p class="text-sm text-neutral-400">No further status changes available for this order.</p>
        @endif
    </div>
</x-layouts.app>
