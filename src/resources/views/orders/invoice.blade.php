<x-layouts.app>
    <x-slot:title>Invoice {{ $order->order_number }}</x-slot:title>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-8">
            <div class="flex items-start justify-between mb-8">
                <div>
                    <div class="text-xl font-bold text-[#1A1A24]">SalmaDrive</div>
                    <div class="text-xs text-neutral-400">Tax invoice</div>
                </div>
                <div class="text-right text-sm">
                    <div class="font-mono text-neutral-800">{{ $order->order_number }}</div>
                    <div class="text-neutral-400">{{ $order->created_at->format('d M Y') }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-8 text-sm">
                <div>
                    <div class="text-xs text-neutral-400 uppercase tracking-wide mb-1">Billed to</div>
                    <div class="text-neutral-800">{{ $order->buyer_name }}</div>
                    <div class="text-neutral-500">{{ $order->buyer_email }}</div>
                    <div class="text-neutral-500">{{ $order->buyer_phone }}</div>
                    <div class="text-neutral-500">{{ $order->buyer_address }}, {{ $order->buyer_city }}</div>
                </div>
                <div>
                    <div class="text-xs text-neutral-400 uppercase tracking-wide mb-1">Sold by</div>
                    <div class="text-neutral-800">{{ $order->vendor?->name }}</div>
                    <div class="text-neutral-500">{{ $order->fulfilment_track === 'fbs' ? 'Fulfilled by Salma' : 'Vendor-fulfilled' }}</div>
                    <div class="text-neutral-500">{{ $order->payment_method === 'cod' ? 'Cash on delivery' : 'Prepaid' }}</div>
                </div>
            </div>

            <table class="w-full text-sm mb-6">
                <thead>
                    <tr class="border-b border-neutral-200 text-neutral-400 text-xs uppercase">
                        <th class="text-left font-medium py-2">Item</th>
                        <th class="text-center font-medium py-2">Qty</th>
                        <th class="text-right font-medium py-2">Unit</th>
                        <th class="text-right font-medium py-2">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="py-2 text-neutral-800">{{ $item->title }}</td>
                            <td class="py-2 text-center text-neutral-500 tabular-nums">{{ $item->quantity }}</td>
                            <td class="py-2 text-right text-neutral-500 tabular-nums">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="py-2 text-right text-neutral-800 tabular-nums">{{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="flex justify-end">
                <div class="w-56 text-sm space-y-1">
                    <div class="flex justify-between text-neutral-500"><span>Subtotal</span><span class="tabular-nums">ZWL {{ number_format($order->subtotal, 2) }}</span></div>
                    <div class="flex justify-between text-neutral-500"><span>Delivery</span><span class="tabular-nums">ZWL {{ number_format($order->delivery_fee, 2) }}</span></div>
                    <div class="flex justify-between font-semibold text-neutral-900 border-t border-neutral-200 pt-1"><span>Total ({{ $order->currency }})</span><span class="tabular-nums">{{ number_format($order->total, 2) }}</span></div>
                </div>
            </div>

            <p class="text-xs text-neutral-400 mt-8 text-center">Thank you for shopping with SalmaDrive.</p>
        </div>

        <div class="text-center mt-4">
            <button onclick="window.print()" class="text-sm text-[#3DB8E8] hover:underline">Print / Save as PDF</button>
        </div>
    </div>
</x-layouts.app>
