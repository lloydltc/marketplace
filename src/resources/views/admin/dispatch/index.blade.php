<x-layouts.app>
    <x-slot:title>Dispatch</x-slot:title>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-2xl font-semibold text-neutral-900 mb-6">Dispatch</h1>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        <h2 class="text-sm font-semibold text-neutral-700 uppercase tracking-wide mb-3">Awaiting dispatch</h2>
        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden mb-8">
            @forelse ($awaitingDispatch as $order)
                <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100 last:border-0">
                    <div>
                        <div class="font-mono text-sm text-neutral-800">{{ $order->order_number }}</div>
                        <div class="text-xs text-neutral-400">{{ $order->buyer_city }} · {{ $order->payment_method === 'cod' ? 'COD ZWL ' . number_format($order->total, 2) : 'Prepaid' }}</div>
                    </div>
                    <form method="POST" action="{{ route('admin.dispatch.assign', $order) }}" class="flex items-center gap-2">
                        @csrf
                        <select name="rider_id" required class="border border-neutral-300 rounded-lg px-2 py-1.5 text-sm">
                            <option value="">Choose rider…</option>
                            @foreach ($riders as $rider)
                                <option value="{{ $rider->id }}">{{ $rider->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="bg-[#1A1A24] hover:bg-[#080810] text-white text-sm font-medium px-3 py-1.5 rounded-lg">Assign</button>
                    </form>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-neutral-400">Nothing awaiting dispatch.</div>
            @endforelse
        </div>

        <h2 class="text-sm font-semibold text-neutral-700 uppercase tracking-wide mb-3">In progress</h2>
        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
            @forelse ($active as $delivery)
                <div class="flex items-center justify-between px-5 py-3 border-b border-neutral-100 last:border-0 text-sm">
                    <span class="font-mono text-neutral-800">{{ $delivery->order->order_number }}</span>
                    <span class="text-neutral-500">{{ $delivery->rider?->name }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">{{ str_replace('_', ' ', $delivery->status) }}</span>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-neutral-400">No active deliveries.</div>
            @endforelse
        </div>
    </div>
</x-layouts.app>
