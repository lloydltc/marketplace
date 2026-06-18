<x-layouts.app>
    <x-slot:title>Part Requests</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-2xl font-semibold text-neutral-900 mb-1">Open Part Requests</h1>
        <p class="text-sm text-neutral-500 mb-6">Quote on buyer requests to win the sale.</p>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif
        @error('quote')
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $message }}</div>
        @enderror

        @if ($requests->isEmpty())
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm py-16 text-center text-sm text-neutral-400">
                No open requests right now.
            </div>
        @else
            <div class="space-y-4">
                @foreach ($requests as $req)
                    @php $alreadyQuoted = $req->quotes->isNotEmpty(); @endphp
                    <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5" x-data="{ open: false }">
                        <div class="text-sm font-medium text-neutral-900">{{ $req->part_description }}</div>
                        <div class="text-xs text-neutral-500 mt-1 space-x-2">
                            @if ($req->make)<span>{{ $req->make->name }} {{ $req->vehicleModel?->name }}</span><span>·</span>@endif
                            @if ($req->year)<span>{{ $req->year }}</span><span>·</span>@endif
                            <span>{{ $req->location }}</span>
                        </div>

                        @if ($alreadyQuoted)
                            <div class="mt-3 text-xs text-green-700">You've quoted this request (ZWL {{ number_format($req->quotes->first()->price, 2) }}).</div>
                        @else
                            <button type="button" @click="open = !open" class="mt-3 text-sm text-[#F0A820] font-medium hover:underline">Submit a quote →</button>
                            <form x-show="open" x-cloak method="POST" action="{{ route('vendor.requests.quote', $req) }}" class="mt-3 grid grid-cols-2 gap-3">
                                @csrf
                                <input name="price" type="number" step="0.01" min="0.01" required placeholder="Price (ZWL)" class="border border-neutral-300 rounded-lg px-3 py-2 text-sm">
                                <select name="condition" class="border border-neutral-300 rounded-lg px-3 py-2 text-sm">
                                    @foreach (['new','used','refurbished','aftermarket'] as $c)
                                        <option value="{{ $c }}">{{ ucfirst($c) }}</option>
                                    @endforeach
                                </select>
                                <input name="delivery_estimate" placeholder="Delivery estimate (e.g. 2–3 days)" class="col-span-2 border border-neutral-300 rounded-lg px-3 py-2 text-sm">
                                <input name="notes" placeholder="Notes (optional)" class="col-span-2 border border-neutral-300 rounded-lg px-3 py-2 text-sm">
                                <button type="submit" class="col-span-2 bg-[#1A1A24] hover:bg-[#080810] text-white font-semibold py-2 rounded-lg text-sm">Send quote</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="mt-6">{{ $requests->links() }}</div>
        @endif
    </div>
</x-layouts.app>
