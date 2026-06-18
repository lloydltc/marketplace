<x-layouts.app>
    <x-slot:title>Request</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <a href="{{ route('rfq.index') }}" class="text-sm text-[#3DB8E8] hover:underline">← My requests</a>

        @if (session('status'))
            <div class="mt-3 mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif
        @error('rfq')
            <div class="mt-3 mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $message }}</div>
        @enderror

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 my-4">
            <div class="flex items-start justify-between">
                <h1 class="text-lg font-semibold text-neutral-900">{{ $request->part_description }}</h1>
                <span class="text-xs px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600 shrink-0">{{ ucfirst($request->status) }}</span>
            </div>
            <div class="text-sm text-neutral-500 mt-2 space-x-2">
                @if ($request->make)<span>{{ $request->make->name }} {{ $request->vehicleModel?->name }}</span><span>·</span>@endif
                @if ($request->year)<span>{{ $request->year }}</span><span>·</span>@endif
                <span>{{ $request->location }}</span>
                @if ($request->budget_max)<span>·</span><span>Budget up to ZWL {{ number_format($request->budget_max, 2) }}</span>@endif
            </div>
            @if ($request->paidDeposit())
                <div class="mt-2 text-xs text-green-700">Commitment deposit of ZWL {{ number_format($request->paidDeposit()->amount, 2) }} held — credited against your order.</div>
            @endif
        </div>

        <h2 class="text-sm font-semibold text-neutral-700 uppercase tracking-wide mb-3">Quotes ({{ $request->quotes->count() }})</h2>

        @if ($request->quotes->isEmpty())
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm py-12 text-center text-sm text-neutral-400">
                No quotes yet — vendors are reviewing your request.
            </div>
        @else
            <div class="space-y-3">
                @foreach ($request->quotes->sortBy('price') as $quote)
                    <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-4"
                         x-data="{ open: false }">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-neutral-900">{{ $quote->vendor?->name }}</div>
                                <div class="text-xs text-neutral-500">
                                    {{ ucfirst($quote->condition) }}@if ($quote->delivery_estimate) · {{ $quote->delivery_estimate }}@endif
                                    @if ($quote->status === 'accepted')<span class="text-green-600 font-medium"> · Accepted</span>@endif
                                </div>
                                @if ($quote->notes)<div class="text-xs text-neutral-400 mt-1">{{ $quote->notes }}</div>@endif
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-neutral-900 tabular-nums">ZWL {{ number_format($quote->price, 2) }}</div>
                                @if ($request->isOpenForQuotes() && $quote->isActive())
                                    <button type="button" @click="open = !open" class="text-sm text-[#F0A820] font-medium hover:underline">Accept →</button>
                                @endif
                            </div>
                        </div>

                        @if ($request->isOpenForQuotes() && $quote->isActive())
                            <form x-show="open" x-cloak method="POST" action="{{ route('rfq.accept', [$request, $quote]) }}"
                                  class="mt-3 pt-3 border-t border-neutral-100 grid grid-cols-2 gap-3">
                                @csrf
                                <input name="full_name" required placeholder="Full name" value="{{ $request->buyer?->name }}" class="border border-neutral-300 rounded-lg px-3 py-2 text-sm">
                                <input name="email" type="email" required placeholder="Email" value="{{ $request->buyer?->email }}" class="border border-neutral-300 rounded-lg px-3 py-2 text-sm">
                                <input name="phone" required placeholder="Phone" class="border border-neutral-300 rounded-lg px-3 py-2 text-sm">
                                <input name="city" required placeholder="City" value="{{ $request->location }}" class="border border-neutral-300 rounded-lg px-3 py-2 text-sm">
                                <input name="address" required placeholder="Delivery address" class="col-span-2 border border-neutral-300 rounded-lg px-3 py-2 text-sm">
                                <button type="submit" class="col-span-2 bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold py-2 rounded-lg text-sm">Accept &amp; checkout</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if ($request->isOpenForQuotes())
            <form method="POST" action="{{ route('rfq.close', $request) }}" class="mt-6 text-center"
                  onsubmit="return confirm('Close this request?')">
                @csrf
                <button type="submit" class="text-sm text-neutral-400 hover:text-red-500">Close request</button>
            </form>
        @endif
    </div>
</x-layouts.app>
