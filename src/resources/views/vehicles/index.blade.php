<x-layouts.app>
    <x-slot:title>Vehicles for Sale</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-6">
            <h1 class="text-3xl font-semibold text-neutral-900">Vehicles for Sale</h1>
            <p class="text-sm text-neutral-500 mt-1">Browse cars, trucks, and more from dealers and private sellers.</p>
        </div>

        <form method="GET" class="space-y-3 mb-3">
            <div class="flex flex-wrap gap-3">
                <x-search-autocomplete name="search" :endpoint="route('search.vehicles')"
                                       :value="request('search')" placeholder="Search make, model or year…" />

                <select name="make_id"
                        class="border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                    <option value="">All makes</option>
                    @foreach ($makes as $make)
                        <option value="{{ $make->id }}" @selected(request('make_id') === $make->id)>{{ $make->name }}</option>
                    @endforeach
                </select>

                <select name="condition"
                        class="border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                    <option value="">All conditions</option>
                    <option value="new"     @selected(request('condition') === 'new')>New</option>
                    <option value="used"    @selected(request('condition') === 'used')>Used</option>
                    <option value="salvage" @selected(request('condition') === 'salvage')>Salvage</option>
                    <option value="rebuilt" @selected(request('condition') === 'rebuilt')>Rebuilt</option>
                </select>

                <select name="fuel_type"
                        class="border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                    <option value="">All fuel types</option>
                    <option value="petrol"   @selected(request('fuel_type') === 'petrol')>Petrol</option>
                    <option value="diesel"   @selected(request('fuel_type') === 'diesel')>Diesel</option>
                    <option value="electric" @selected(request('fuel_type') === 'electric')>Electric</option>
                    <option value="hybrid"   @selected(request('fuel_type') === 'hybrid')>Hybrid</option>
                </select>

                <select name="sort"
                        class="border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                    <option value="latest"    @selected(request('sort', 'latest') === 'latest')>Latest</option>
                    <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: Low to High</option>
                    <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: High to Low</option>
                    <option value="year_desc" @selected(request('sort') === 'year_desc')>Year: Newest</option>
                    <option value="mileage_asc" @selected(request('sort') === 'mileage_asc')>Mileage: Lowest</option>
                </select>
            </div>

            {{-- Advanced filters --}}
            <div class="flex flex-wrap gap-3">
                <select name="body_type"
                        class="border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                    <option value="">All body types</option>
                    @foreach (['sedan','hatchback','suv','pickup','van','minivan','wagon','coupe','convertible','bus','truck','other'] as $body)
                        <option value="{{ $body }}" @selected(request('body_type') === $body)>{{ ucfirst($body) }}</option>
                    @endforeach
                </select>
                <select name="transmission"
                        class="border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                    <option value="">Any transmission</option>
                    <option value="manual"    @selected(request('transmission') === 'manual')>Manual</option>
                    <option value="automatic" @selected(request('transmission') === 'automatic')>Automatic</option>
                    <option value="cvt"       @selected(request('transmission') === 'cvt')>CVT</option>
                </select>
                <input type="number" name="year_min" value="{{ request('year_min') }}" placeholder="Year from" min="1900"
                       class="w-28 border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                <input type="number" name="year_max" value="{{ request('year_max') }}" placeholder="Year to" min="1900"
                       class="w-28 border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min price" min="0"
                       class="w-32 border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max price" min="0"
                       class="w-32 border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">

                <button type="submit"
                        class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                    Search
                </button>
                @if (request()->hasAny(['search', 'make_id', 'condition', 'fuel_type', 'sort', 'body_type', 'transmission', 'year_min', 'year_max', 'min_price', 'max_price']))
                    <a href="{{ route('vehicles.index') }}"
                       class="text-sm text-neutral-500 hover:text-neutral-700 px-2 py-2 self-center">Clear</a>
                @endif
            </div>
        </form>

        {{-- Save current search (signed-in users, active filters only) --}}
        <div class="mb-8 min-h-[2rem]">
            <x-save-search type="vehicles" :active="request()->hasAny(['search', 'make_id', 'condition', 'fuel_type', 'body_type', 'transmission', 'year_min', 'year_max', 'min_price', 'max_price'])" />
        </div>

        @if ($vehicles->isEmpty())
            <div class="py-16 space-y-6">
                <p class="text-neutral-500 text-center">No vehicles match your search.</p>
                <x-rfq-cta context="vehicles" :query="request('search', '')" />
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @foreach ($vehicles as $vehicle)
                    <a href="{{ route('vehicles.show', $vehicle) }}"
                       class="group bg-white border border-neutral-200 rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">
                        {{-- Placeholder image area --}}
                        <div class="aspect-video bg-neutral-100 flex items-center justify-center text-neutral-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 17H5a2 2 0 01-2-2v-4l2-5h10l2 5v4a2 2 0 01-2 2h-3m-4 0h4m-4 0v-4h4v4" />
                            </svg>
                        </div>

                        <div class="p-4 flex flex-col flex-1">
                            @if ($vehicle->isFeatured())
                                <span class="self-start mb-2 inline-flex items-center gap-1 text-xs font-semibold bg-[#F0A820]/15 text-[#B5790F] px-2 py-0.5 rounded-full">
                                    ★ Featured
                                </span>
                            @endif
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <h2 class="text-sm font-semibold text-neutral-900 group-hover:text-[#F0A820] transition-colors leading-snug">
                                    {{ $vehicle->displayTitle() }}
                                </h2>
                                <span class="shrink-0 text-xs capitalize text-neutral-500">{{ $vehicle->condition }}</span>
                            </div>

                            <div class="text-xs text-neutral-500 space-x-2 mb-3">
                                <span class="capitalize">{{ $vehicle->body_type }}</span>
                                <span>·</span>
                                <span class="uppercase">{{ $vehicle->transmission }}</span>
                                <span>·</span>
                                <span class="capitalize">{{ $vehicle->fuel_type }}</span>
                            </div>

                            <div class="mt-auto flex items-end justify-between">
                                <div>
                                    <div class="text-base font-bold text-neutral-900 tabular-nums">
                                        ZWL {{ number_format($vehicle->price_zwl, 2) }}
                                    </div>
                                    @if ($vehicle->price_usd)
                                        <div class="text-xs text-neutral-400 tabular-nums">
                                            USD {{ number_format($vehicle->price_usd, 2) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="text-right">
                                    @if ($vehicle->isListedByVendor())
                                        <span class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full">Dealer</span>
                                    @else
                                        <span class="text-xs bg-purple-50 text-purple-600 px-2 py-0.5 rounded-full">Private</span>
                                    @endif
                                    <x-unverified-badge :verified="$vehicle->ownerIsVerified()" class="mt-1" />
                                    <div class="text-xs text-neutral-400 mt-1 tabular-nums">
                                        {{ number_format($vehicle->mileage) }} km
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $vehicles->withQueryString()->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
