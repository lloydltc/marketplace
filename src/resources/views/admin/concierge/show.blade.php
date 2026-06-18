<x-layouts.app>
    <x-slot:title>Concierge Request</x-slot:title>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <a href="{{ route('admin.concierge.index') }}" class="text-sm text-[#3DB8E8] hover:underline">← Queue</a>

        @if (session('status'))
            <div class="mt-3 mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif
        @error('concierge')
            <div class="mt-3 mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $message }}</div>
        @enderror

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 my-4">
            <div class="flex items-start justify-between mb-2">
                <h1 class="text-lg font-semibold text-neutral-900">{{ $request->part_description }}</h1>
                <span class="text-xs px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600 shrink-0">{{ ucfirst($request->status) }}</span>
            </div>
            <div class="text-sm text-neutral-500">
                {{ $request->buyer?->name }} · {{ $request->location }}
                @if ($request->make) · {{ $request->make->name }} {{ $request->vehicleModel?->name }} {{ $request->year }}@endif
            </div>
            @if ($request->notes)<div class="text-sm text-neutral-400 mt-2">{{ $request->notes }}</div>@endif
            @if ($request->payment_status === 'paid')<div class="mt-2 text-xs text-green-700 font-medium">Buyer has paid ZWL {{ number_format($request->total, 2) }}.</div>@endif
            @if ($request->settled_at)<div class="text-xs text-blue-700">Vendor settled via wallet.</div>@endif
        </div>

        {{-- Quote (sourcing/new) --}}
        @if (in_array($request->status, ['new', 'sourcing', 'quoted']))
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 mb-4">
                <h2 class="text-sm font-semibold text-neutral-700 mb-3">Quote the buyer</h2>
                <form method="POST" action="{{ route('admin.concierge.quote', $request) }}" class="grid grid-cols-2 gap-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-neutral-500 mb-1">Part value</label>
                        <input name="part_value" type="number" step="0.01" min="0.01" required value="{{ $request->part_value }}" class="block w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-neutral-500 mb-1">Delivery fee</label>
                        <input name="delivery_fee" type="number" step="0.01" min="0" required value="{{ $request->delivery_fee }}" class="block w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs text-neutral-500 mb-1">Sourced from (on-platform vendor — optional)</label>
                        <select name="sourced_vendor_id" class="block w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Off-platform / external</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected($request->sourced_vendor_id === $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="col-span-2 text-xs text-neutral-400">Service fee is computed automatically (settings-driven): max(min, % of part value).</p>
                    <button type="submit" class="col-span-2 bg-[#1A1A24] hover:bg-[#080810] text-white font-semibold py-2 rounded-lg text-sm">Send quote</button>
                </form>
            </div>
        @endif

        {{-- Workflow transitions --}}
        @php
            $next = [
                'new' => [['sourcing', 'Start sourcing']],
                'sourcing' => [['quoted', 'Mark quoted']],
                'paid' => [['fulfilling', 'Start fulfilling']],
                'fulfilling' => [['delivered', 'Mark delivered']],
                'delivered' => [['closed', 'Close & settle']],
            ][$request->status] ?? [];
        @endphp
        @if (! empty($next) || ! in_array($request->status, ['closed','cancelled']))
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5">
                <div class="text-xs font-medium text-neutral-500 uppercase tracking-wide mb-3">Advance workflow</div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($next as [$to, $label])
                        <form method="POST" action="{{ route('admin.concierge.transition', $request) }}">
                            @csrf <input type="hidden" name="to" value="{{ $to }}">
                            <button type="submit" class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] text-sm font-medium px-3 py-1.5 rounded-lg">{{ $label }}</button>
                        </form>
                    @endforeach
                    @unless (in_array($request->status, ['closed','cancelled','paid','fulfilling','delivered']))
                        <form method="POST" action="{{ route('admin.concierge.transition', $request) }}">
                            @csrf <input type="hidden" name="to" value="cancelled">
                            <button type="submit" class="border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium px-3 py-1.5 rounded-lg">Cancel</button>
                        </form>
                    @endunless
                </div>
                @if ($request->status === 'quoted')
                    <p class="text-xs text-neutral-400 mt-2">Waiting for the buyer to pay. Payment moves it to “paid” automatically.</p>
                @endif
            </div>
        @endif
    </div>
</x-layouts.app>
