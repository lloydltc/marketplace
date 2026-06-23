<x-layouts.app>
    <x-slot:title>{{ $q !== '' ? "Search: {$q}" : 'Search' }}</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- Search bar (prominent, re-runnable) --}}
        <form method="GET" action="{{ route('search.index') }}" class="flex gap-3 mb-8 max-w-2xl">
            <input type="text" name="q" value="{{ $q }}" placeholder="Search vehicles, parts, accessories…"
                   class="flex-1 border border-neutral-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
            <button type="submit" class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-6 py-2.5 rounded-lg text-sm transition-colors">Search</button>
        </form>

        @if ($q === '')
            <p class="text-neutral-500 text-sm">Type something above to search across vehicles and parts.</p>
        @else
            @php
                $vehicleCount = $vehicles?->total() ?? 0;
                $productCount = $products?->total() ?? 0;
            @endphp
            <h1 class="text-2xl font-semibold text-neutral-900 mb-1">Results for “{{ $q }}”</h1>
            <p class="text-sm text-neutral-500 mb-8">{{ $vehicleCount }} vehicle{{ $vehicleCount === 1 ? '' : 's' }} · {{ $productCount }} part{{ $productCount === 1 ? '' : 's' }}</p>

            {{-- Both empty → RFQ "Request it" entry point --}}
            @if ($vehicleCount === 0 && $productCount === 0)
                <div class="py-12 space-y-6">
                    <p class="text-neutral-500 text-center">Nothing matched “{{ $q }}”.</p>
                    <x-rfq-cta context="search" :query="$q" />
                </div>
            @else
                {{-- Vehicles --}}
                @if ($vehicleCount > 0)
                    <section class="mb-12">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-neutral-900">Vehicles</h2>
                            <a href="{{ route('vehicles.index', ['search' => $q]) }}" class="text-sm text-[#3DB8E8] hover:underline">View all {{ $vehicleCount }} →</a>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                            @foreach ($vehicles->items() as $vehicle)
                                <a href="{{ route('vehicles.show', $vehicle) }}"
                                   class="group bg-white border border-neutral-200 rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">
                                    <div class="aspect-video bg-neutral-100 flex items-center justify-center overflow-hidden">
                                        <x-listing-thumbnail :cover="$vehicle->coverImage()" :alt="$vehicle->displayTitle()" type="vehicle" />
                                    </div>
                                    <div class="p-4 flex flex-col flex-1">
                                        <h3 class="text-sm font-semibold text-neutral-900 group-hover:text-[#F0A820] transition-colors leading-snug mb-2">{{ $vehicle->displayTitle() }}</h3>
                                        <div class="mt-auto text-base font-bold text-neutral-900 tabular-nums">{{ $vehicle->primaryPrice() }}</div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Parts --}}
                @if ($productCount > 0)
                    <section>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-neutral-900">Parts &amp; accessories</h2>
                            <a href="{{ route('products.index', ['q' => $q]) }}" class="text-sm text-[#3DB8E8] hover:underline">View all {{ $productCount }} →</a>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                            @foreach ($products->items() as $product)
                                <a href="{{ route('products.show', $product) }}"
                                   class="bg-white border border-neutral-200 rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden group">
                                    <div class="bg-neutral-100 h-40 flex items-center justify-center overflow-hidden">
                                        <x-listing-thumbnail :cover="$product->coverImage()" :alt="$product->title" type="product" />
                                    </div>
                                    <div class="p-4">
                                        <div class="text-xs text-neutral-400 mb-1">{{ $product->category?->name }}</div>
                                        <h3 class="text-sm font-semibold text-neutral-900 line-clamp-2 group-hover:text-[#F0A820] transition-colors">{{ $product->title }}</h3>
                                        <div class="mt-3 text-sm font-bold text-neutral-900 tabular-nums">{{ $product->primaryPrice() }}</div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endif
        @endif
    </div>
</x-layouts.app>
