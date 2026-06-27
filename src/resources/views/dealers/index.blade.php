<x-layouts.app>
    <x-slot:title>Find a dealer</x-slot:title>
    <x-slot:metaDescription>Browse verified car dealers on SalmaDrive — view their vehicle and parts inventory.</x-slot:metaDescription>

    {{-- Featured-dealer carousel (paid placement) --}}
    @if ($featured->isNotEmpty())
        <div class="bg-sidebar py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2 mb-5">
                    <h2 class="text-h3 font-semibold text-white">Featured dealers</h2>
                    <span class="text-[10px] font-semibold uppercase tracking-wide bg-[rgb(var(--brand)/0.2)] text-brand px-2 py-0.5 rounded-full">Sponsored</span>
                </div>
                <div class="flex gap-5 overflow-x-auto sd-rail pb-2 snap-x">
                    @foreach ($featured as $dealer)
                        <x-dealer-card :dealer="$dealer" :featured="true" class="snap-start shrink-0 w-72" />
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-h1 text-ink">Find a dealer</h1>
                <p class="text-body-sm text-muted mt-1">{{ number_format($dealers->total()) }} verified {{ Str::plural('dealer', $dealers->total()) }} on SalmaDrive.</p>
            </div>
            <form method="GET" class="flex gap-2">
                <input type="text" name="q" value="{{ $term }}" placeholder="Search dealers…"
                       class="h-11 px-3.5 rounded-md bg-surface text-ink border border-strong placeholder:text-[rgb(var(--text-muted))] focus-visible:outline-none focus:ring-2 focus:ring-brand focus:border-brand text-body-sm">
                <x-button type="submit">Search</x-button>
            </form>
        </div>

        @if ($dealers->isEmpty())
            <x-empty title="No dealers found{{ $term ? ' for “' . $term . '”' : '' }}"
                     message="Try a different search, or browse all listings." />
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($dealers as $dealer)
                    <x-dealer-card :dealer="$dealer" />
                @endforeach
            </div>
            <x-pagination :paginator="$dealers->withQueryString()" class="mt-8" />
        @endif
    </div>
</x-layouts.app>
