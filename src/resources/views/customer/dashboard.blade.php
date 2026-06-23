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
    </div>

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
