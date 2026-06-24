<x-layouts.app>
    <x-slot:title>Find a Dealer</x-slot:title>
    <x-slot:metaDescription>Browse verified car dealers on SalmaDrive — view their vehicle and parts inventory.</x-slot:metaDescription>

    {{-- Featured-dealer carousel (paid placement) --}}
    @if ($featured->isNotEmpty())
        <div class="bg-[#1A1A24] py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2 mb-5">
                    <h2 class="text-lg font-semibold text-white">Featured dealers</h2>
                    <span class="text-[10px] font-semibold uppercase tracking-wide bg-[#F0A820]/20 text-[#F0A820] px-2 py-0.5 rounded-full">Sponsored</span>
                </div>
                <div class="flex gap-4 overflow-x-auto pb-2 snap-x">
                    @foreach ($featured as $dealer)
                        <div class="snap-start shrink-0 w-72">
                            <x-dealer-card :dealer="$dealer" :featured="true" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-semibold text-neutral-900">Find a Dealer</h1>
                <p class="text-sm text-neutral-500 mt-1">{{ number_format($dealers->total()) }} verified {{ Str::plural('dealer', $dealers->total()) }} on SalmaDrive.</p>
            </div>
            <form method="GET" class="flex gap-2">
                <input type="text" name="q" value="{{ $term }}" placeholder="Search dealers…"
                       class="border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                <button type="submit" class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm transition-colors">Search</button>
            </form>
        </div>

        @if ($dealers->isEmpty())
            <div class="bg-white border border-neutral-200 rounded-xl py-16 text-center text-sm text-neutral-500">
                No dealers found{{ $term ? ' for “' . $term . '”' : '' }}.
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($dealers as $dealer)
                    <x-dealer-card :dealer="$dealer" />
                @endforeach
            </div>
            <div class="mt-8">{{ $dealers->links() }}</div>
        @endif
    </div>
</x-layouts.app>
