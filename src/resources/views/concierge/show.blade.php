<x-layouts.app>
    <x-slot:title>Concierge Request</x-slot:title>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <a href="{{ route('concierge.index') }}" class="text-sm text-[#3DB8E8] hover:underline">← My requests</a>

        @if (session('status'))
            <div class="mt-3 mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif
        @error('payment')
            <div class="mt-3 mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $message }}</div>
        @enderror

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 my-4">
            <div class="flex items-start justify-between mb-2">
                <h1 class="text-lg font-semibold text-neutral-900">{{ $request->part_description }}</h1>
                <span class="text-xs px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600 shrink-0">{{ ucfirst($request->status) }}</span>
            </div>
            <div class="text-sm text-neutral-500">{{ $request->location }}</div>

            {{-- Progress --}}
            <ol class="flex flex-wrap gap-2 mt-4 text-xs">
                @foreach (['new' => 'Received', 'sourcing' => 'Sourcing', 'quoted' => 'Quoted', 'paid' => 'Paid', 'fulfilling' => 'Fulfilling', 'delivered' => 'Delivered', 'closed' => 'Done'] as $key => $label)
                    @php $reached = array_search($request->status, ['new','sourcing','quoted','paid','fulfilling','delivered','closed']) >= array_search($key, ['new','sourcing','quoted','paid','fulfilling','delivered','closed']); @endphp
                    <li class="px-2 py-0.5 rounded-full {{ $reached ? 'bg-[#F0A820]/15 text-[#B5790F]' : 'bg-neutral-100 text-neutral-400' }}">{{ $label }}</li>
                @endforeach
            </ol>
        </div>

        @if ($request->total)
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5">
                <h2 class="text-sm font-semibold text-neutral-700 mb-3">Your quote</h2>
                <div class="text-sm space-y-1">
                    <div class="flex justify-between text-neutral-500"><span>Part</span><span class="tabular-nums">ZWL {{ number_format($request->part_value, 2) }}</span></div>
                    <div class="flex justify-between text-neutral-500"><span>Concierge service fee</span><span class="tabular-nums">ZWL {{ number_format($request->service_fee, 2) }}</span></div>
                    <div class="flex justify-between text-neutral-500"><span>Delivery</span><span class="tabular-nums">ZWL {{ number_format($request->delivery_fee, 2) }}</span></div>
                    <div class="flex justify-between font-semibold text-neutral-900 border-t border-neutral-100 pt-1"><span>Total</span><span class="tabular-nums">ZWL {{ number_format($request->total, 2) }}</span></div>
                </div>

                @if ($request->isAwaitingPayment())
                    <form method="POST" action="{{ route('concierge.pay', $request) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold py-2.5 rounded-lg text-sm">Pay with Pesepay</button>
                    </form>
                @elseif ($request->isPaid())
                    <div class="mt-4 text-center text-sm text-green-700 font-medium">Paid — we're on it.</div>
                @endif
            </div>
        @else
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 text-center text-sm text-neutral-500">
                Our team is sourcing your part and will send a quote shortly.
            </div>
        @endif
    </div>
</x-layouts.app>
