<x-layouts.app>
    <x-slot:title>My Orders</x-slot:title>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-2xl font-semibold text-neutral-900 mb-6">My Orders</h1>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        @if ($orders->isEmpty())
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm py-16 text-center">
                <p class="text-sm text-neutral-500">You haven't placed any orders yet.</p>
                <a href="{{ route('products.index') }}" class="mt-3 inline-block text-sm text-[#3DB8E8] hover:underline">Browse products</a>
            </div>
        @else
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm divide-y divide-neutral-100">
                @foreach ($orders as $order)
                    <a href="{{ route('orders.show', $order) }}" class="flex items-center justify-between px-5 py-4 hover:bg-neutral-50">
                        <div>
                            <div class="font-mono text-sm text-neutral-800">{{ $order->order_number }}</div>
                            <div class="text-xs text-neutral-400">
                                {{ $order->created_at->format('d M Y') }} · {{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}
                            </div>
                        </div>
                        <div class="text-right">
                            <x-order-status :status="$order->status" />
                            <div class="text-sm font-semibold text-neutral-900 tabular-nums mt-1">ZWL {{ number_format($order->total, 2) }}</div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">{{ $orders->links() }}</div>
        @endif
    </div>
</x-layouts.app>
