<x-layouts.app>
    <x-slot:title>Sales</x-slot:title>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-h1 text-ink mb-1">Sales</h1>
        <p class="text-body-sm text-muted mb-6">Orders customers have placed against your listings.</p>

        @if (session('status'))
            <div class="mb-5 bg-[rgb(var(--success)/0.12)] border border-[rgb(var(--success)/0.3)] text-[rgb(var(--success))] text-body-sm rounded-lg px-4 py-3" role="status">{{ session('status') }}</div>
        @endif

        @if ($orders->isEmpty())
            <x-empty title="No orders yet" message="When customers buy your products, their orders appear here." />
        @else
            <x-table>
                <x-slot:head>
                    <th>Order</th>
                    <th class="hidden sm:table-cell">Buyer</th>
                    <th>Status</th>
                    <th class="!text-right">Total</th>
                    <th></th>
                </x-slot:head>
                @foreach ($orders as $order)
                    <tr>
                        <td>
                            <div class="font-mono text-ink">{{ $order->order_number }}</div>
                            <div class="text-caption text-muted">{{ $order->created_at->format('d M Y') }}</div>
                        </td>
                        <td class="text-[rgb(var(--text))] hidden sm:table-cell">{{ $order->buyer_name }}</td>
                        <td><x-order-status :status="$order->status" /></td>
                        <td class="text-right tabular-nums text-ink">ZWL {{ number_format($order->total, 2) }}</td>
                        <td class="text-right">
                            <a href="{{ route('vendor.orders.show', $order) }}" class="text-body-sm text-[rgb(var(--info))] hover:underline">Manage</a>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <x-pagination :paginator="$orders->withQueryString()" class="mt-6" />
        @endif
    </div>
</x-layouts.app>
