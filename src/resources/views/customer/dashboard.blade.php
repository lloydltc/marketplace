<x-layouts.app>
    <x-slot:title>SalmaDrive — Find It. Buy It. Drive It.</x-slot:title>

    {{-- Hero --}}
    <div class="bg-[#1A1A24] py-20 px-4 text-center">
        <p class="text-[#F0A820] text-xs font-semibold tracking-[0.2em] uppercase mb-3">Zimbabwe's trusted marketplace</p>
        <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4 leading-tight">
            Find It. Buy It.<br>
            <span class="text-[#F0A820]">Drive It.</span>
        </h1>
        <p class="text-neutral-400 text-base max-w-md mx-auto mb-8">
            Browse thousands of vehicles, parts, and accessories from verified dealers and private sellers.
        </p>
        <form method="GET" action="{{ route('search.index') }}"
              class="flex flex-col sm:flex-row items-center justify-center gap-3 max-w-md mx-auto">
            <input type="text" name="q" placeholder="Search vehicles, parts, accessories…"
                   class="flex-1 w-full bg-white border-0 rounded-lg px-4 py-3 text-neutral-900 placeholder-neutral-400 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]">
            <button type="submit"
                    class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-6 py-3 rounded-lg text-sm transition-colors w-full sm:w-auto">
                Search
            </button>
        </form>

        {{-- H0/H6: browse by type (count-driven) --}}
        <div class="flex flex-wrap items-center justify-center gap-2 mt-6">
            @foreach (config('vehicle_types.types') as $key => $cfg)
                @php $tc = $typeCounts[$key] ?? 0; @endphp
                <a href="{{ route('vehicles.index', ['vehicle_type' => $key]) }}"
                   class="inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 text-white text-sm font-medium px-4 py-2 rounded-full transition-colors">
                    <span aria-hidden="true">{{ $cfg['icon'] }}</span> {{ $cfg['plural'] }}
                    @if ($tc > 0)
                        <span class="text-white/50 text-xs tabular-nums">{{ number_format($tc) }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>

    {{-- H6: browse by make (count-driven, only makes with live inventory) --}}
    @if ($popularMakes->isNotEmpty())
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-neutral-900">Browse by make</h2>
                <a href="{{ route('vehicles.index') }}" class="text-sm text-[#3DB8E8] hover:underline">All makes →</a>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($popularMakes as $make)
                    <a href="{{ route('vehicles.index', ['make_id' => $make->id]) }}"
                       class="inline-flex items-center gap-1.5 bg-white border border-neutral-200 hover:border-neutral-400 text-neutral-700 text-sm font-medium px-4 py-2 rounded-full transition-colors">
                        {{ $make->name }}
                        <span class="text-neutral-400 text-xs tabular-nums">{{ number_format($make->total) }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- H8: featured-dealer carousel (paid placement) --}}
    @if ($featuredDealers->isNotEmpty())
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-neutral-900 flex items-center gap-2">
                    Featured dealers
                    <span class="text-[10px] font-semibold uppercase tracking-wide bg-[#F0A820]/15 text-[#B5790F] px-2 py-0.5 rounded-full">Sponsored</span>
                </h2>
                <a href="{{ route('dealers.index') }}" class="text-sm text-[#3DB8E8] hover:underline">Find a dealer →</a>
            </div>
            <div class="flex gap-4 overflow-x-auto pb-2 snap-x">
                @foreach ($featuredDealers as $dealer)
                    <div class="snap-start shrink-0 w-72">
                        <x-dealer-card :dealer="$dealer" :featured="true" />
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- H7: sponsored (paid placement) row --}}
    <x-vehicle-row title="Sponsored listings" :vehicles="$sponsored" :sponsored="true"
                   :view-all="route('vehicles.index')" />

    {{-- H7: recently viewed (cookie-backed, per browser) --}}
    <x-vehicle-row title="Recently viewed" :vehicles="$recentlyViewed" />

    {{-- Vehicles for sale --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-neutral-900">Vehicles for sale</h2>
            <a href="{{ route('vehicles.index') }}" class="text-sm text-[#3DB8E8] hover:underline">View all →</a>
        </div>

        @if (empty($vehicles))
            <p class="text-sm text-neutral-500">No vehicles listed yet — check back soon.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ($vehicles as $vehicle)
                    <a href="{{ route('vehicles.show', $vehicle) }}"
                       class="group bg-white border border-neutral-200 rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">
                        <div class="aspect-video bg-neutral-100 flex items-center justify-center overflow-hidden">
                            <x-listing-thumbnail :cover="$vehicle->coverImage()" :alt="$vehicle->displayTitle()" type="vehicle" />
                        </div>
                        <div class="p-4 flex flex-col flex-1">
                            @if ($vehicle->isFeatured())
                                <span class="self-start mb-2 text-xs font-semibold bg-[#F0A820]/15 text-[#B5790F] px-2 py-0.5 rounded-full">★ Featured</span>
                            @endif
                            <h3 class="text-sm font-semibold text-neutral-900 group-hover:text-[#F0A820] transition-colors leading-snug mb-2">
                                {{ $vehicle->displayTitle() }}
                            </h3>
                            <div class="mt-auto text-base font-bold text-neutral-900 tabular-nums">
                                {{ $vehicle->primaryPrice() }}
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Parts & accessories --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-neutral-900">Parts & accessories</h2>
            <a href="{{ route('products.index') }}" class="text-sm text-[#3DB8E8] hover:underline">View all →</a>
        </div>

        @if (empty($products))
            <p class="text-sm text-neutral-500">No products listed yet — check back soon.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ($products as $product)
                    <a href="{{ route('products.show', $product) }}"
                       class="bg-white border border-neutral-200 rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden group">
                        <div class="bg-neutral-100 h-40 flex items-center justify-center overflow-hidden">
                            <x-listing-thumbnail :cover="$product->coverImage()" :alt="$product->title" type="product" />
                        </div>
                        <div class="p-4">
                            <div class="text-xs text-neutral-400 mb-1">{{ $product->category?->name }}</div>
                            <h3 class="text-sm font-semibold text-neutral-900 line-clamp-2 group-hover:text-[#F0A820] transition-colors">
                                {{ $product->title }}
                            </h3>
                            <div class="mt-3 text-sm font-bold text-neutral-900 tabular-nums">
                                {{ $product->primaryPrice() }}
                            </div>
                            <div class="mt-1 text-xs text-neutral-400">{{ $product->vendor?->name }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

</x-layouts.app>
