<x-layouts.app>
    <x-slot:title>Sales</x-slot:title>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-2xl font-semibold text-neutral-900 mb-1">Sales</h1>
        <p class="text-sm text-neutral-500 mb-6">Orders customers have placed against your listings.</p>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        @if ($orders->isEmpty())
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm py-16 text-center">
                <p class="text-sm text-neutral-500">No orders yet.</p>
            </div>
        @else
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-neutral-50 border-b border-neutral-200 text-neutral-500">
                            <th class="text-left font-medium px-4 py-3">Order</th>
                            <th class="text-left font-medium px-4 py-3 hidden sm:table-cell">Buyer</th>
                            <th class="text-left font-medium px-4 py-3">Status</th>
                            <th class="text-right font-medium px-4 py-3">Total</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($orders as $order)
                            <tr class="hover:bg-neutral-50">
                                <td class="px-4 py-3">
                                    <div class="font-mono text-neutral-800">{{ $order->order_number }}</div>
                                    <div class="text-xs text-neutral-400">{{ $order->created_at->format('d M Y') }}</div>
                                </td>
                                <td class="px-4 py-3 text-neutral-600 hidden sm:table-cell">{{ $order->buyer_name }}</td>
                                <td class="px-4 py-3"><x-order-status :status="$order->status" /></td>
                                <td class="px-4 py-3 text-right tabular-nums text-neutral-800">ZWL {{ number_format($order->total, 2) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('vendor.orders.show', $order) }}" class="text-sm text-[#3DB8E8] hover:underline">Manage</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">{{ $orders->links() }}</div>
        @endif
    </div>
</x-layouts.app>
