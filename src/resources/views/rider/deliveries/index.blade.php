<x-layouts.app>
    <x-slot:title>My Deliveries</x-slot:title>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-semibold text-neutral-900 mb-4">My Deliveries</h1>

        @if (session('status'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        {{-- Today's cash position --}}
        <div class="bg-[#1A1A24] text-white rounded-xl p-4 mb-5 flex items-center justify-between">
            <div>
                <div class="text-xs text-neutral-400 uppercase tracking-wide">COD to hand in today</div>
                <div class="text-2xl font-bold tabular-nums">ZWL {{ number_format($session->expected_total, 2) }}</div>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full {{ $session->status === 'reconciled' ? 'bg-green-500/20 text-green-300' : 'bg-[#F0A820]/20 text-[#F0A820]' }}">
                {{ ucfirst($session->status) }}
            </span>
        </div>

        @if ($deliveries->isEmpty())
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm py-12 text-center text-sm text-neutral-400">
                No active deliveries right now.
            </div>
        @else
            <div class="space-y-4">
                @foreach ($deliveries as $delivery)
                    <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-mono text-sm text-neutral-800">{{ $delivery->order->order_number }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600">{{ str_replace('_', ' ', $delivery->status) }}</span>
                        </div>
                        <div class="text-sm text-neutral-600">{{ $delivery->order->buyer_name }} · {{ $delivery->order->buyer_phone }}</div>
                        <div class="text-sm text-neutral-500 mb-3">{{ $delivery->order->buyer_address }}, {{ $delivery->order->buyer_city }}</div>

                        @if ($delivery->isCod())
                            <div class="text-sm font-medium text-amber-700 mb-3">Collect cash: ZWL {{ number_format($delivery->cod_expected, 2) }}</div>
                        @else
                            <div class="text-sm text-green-700 mb-3">Prepaid — no cash to collect</div>
                        @endif

                        @if ($delivery->status === 'assigned')
                            <form method="POST" action="{{ route('rider.deliveries.pickup', $delivery) }}">
                                @csrf
                                <button type="submit" class="w-full bg-[#1A1A24] hover:bg-[#080810] text-white font-semibold py-2 rounded-lg text-sm">Confirm pickup</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('rider.deliveries.deliver', $delivery) }}" class="space-y-2">
                                @csrf
                                @if ($delivery->isCod())
                                    <input type="number" name="cod_collected" step="0.01" min="0" required
                                           value="{{ $delivery->cod_expected }}" placeholder="Cash collected"
                                           class="block w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm">
                                @endif
                                <input type="text" name="proof_note" placeholder="Proof note (who received, etc.)"
                                       class="block w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm">
                                <button type="submit" class="w-full bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold py-2 rounded-lg text-sm">Mark delivered</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
