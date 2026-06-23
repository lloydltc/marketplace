<x-layouts.app>
    <x-slot:title>Vehicles for Sale</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-6">
            <h1 class="text-3xl font-semibold text-neutral-900">
                {{ request('vehicle_type') ? config('vehicle_types.types.' . request('vehicle_type') . '.plural', 'Vehicles') . ' for Sale' : 'Vehicles for Sale' }}
            </h1>
            <p class="text-sm text-neutral-500 mt-1">Browse from dealers and private sellers.</p>
        </div>

        {{-- H0/H6: listing-type tabs --}}
        @include('partials.vehicle-type-tabs')

        <div x-data="{ filtersOpen: false }" class="mb-3">
            {{-- Mobile: filters live in a drawer, not a long scroll (UI_STANDARDS) --}}
            <button type="button" @click="filtersOpen = !filtersOpen"
                    class="sm:hidden mb-3 w-full flex items-center justify-center gap-2 border border-neutral-300 rounded-lg px-4 py-2 text-sm font-medium text-neutral-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M6 8h12M9 12h6M11 16h2"/></svg>
                <span x-text="filtersOpen ? 'Hide filters' : 'Filters'">Filters</span>
            </button>
        <form method="GET" class="sm:block space-y-3" :class="{ 'hidden': !filtersOpen }">
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

            </div>

            {{-- Dynamic feature facets (D3 — driven by D4's filterable definitions) --}}
            @if (isset($filterableFeatures) && $filterableFeatures->isNotEmpty())
                <div class="flex flex-wrap gap-3 pt-3 border-t border-neutral-100">
                    @foreach ($filterableFeatures as $def)
                        @php $fv = request("features.{$def->id}"); @endphp
                        @if ($def->type === 'boolean')
                            <select name="features[{{ $def->id }}]"
                                    class="border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                                <option value="">{{ $def->name }}: any</option>
                                <option value="1" @selected($fv === '1')>{{ $def->name }}: Yes</option>
                                <option value="0" @selected($fv === '0')>{{ $def->name }}: No</option>
                            </select>
                        @elseif ($def->type === 'enum')
                            <select name="features[{{ $def->id }}]"
                                    class="border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                                <option value="">{{ $def->name }}: any</option>
                                @foreach (($def->options ?? []) as $opt)
                                    <option value="{{ $opt }}" @selected((string) $fv === (string) $opt)>{{ $def->name }}: {{ $opt }}</option>
                                @endforeach
                            </select>
                        @elseif ($def->type === 'number')
                            <input type="number" name="features[{{ $def->id }}]" value="{{ $fv }}"
                                   placeholder="{{ $def->name }}{{ $def->unit ? ' (' . $def->unit . ')' : '' }}" min="0"
                                   class="w-40 border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex flex-wrap items-center gap-3 pt-1">
                <button type="submit"
                        class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                    Search
                </button>
                @if (request()->hasAny(['search', 'make_id', 'condition', 'fuel_type', 'sort', 'body_type', 'transmission', 'year_min', 'year_max', 'min_price', 'max_price', 'features']))
                    <a href="{{ route('vehicles.index') }}"
                       class="text-sm text-neutral-500 hover:text-neutral-700 px-2 py-2 self-center">Clear</a>
                @endif
            </div>
        </form>
        </div>

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
                        <div class="aspect-video bg-neutral-100 flex items-center justify-center overflow-hidden">
                            <x-listing-thumbnail :cover="$vehicle->coverImage()" :alt="$vehicle->displayTitle()" type="vehicle" />
                        </div>

                        <div class="p-4 flex flex-col flex-1">
                            <div class="flex flex-wrap gap-1 mb-2">
                                @if ($vehicle->isFeatured())
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold bg-[#F0A820]/15 text-[#B5790F] px-2 py-0.5 rounded-full">★ Featured</span>
                                @endif
                                @if ($vehicle->is_recent_import)
                                    <span class="text-xs font-semibold bg-[#3DB8E8]/15 text-[#1E7FA8] px-2 py-0.5 rounded-full">Recent import</span>
                                @endif
                            </div>
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
                                        {{ $vehicle->primaryPrice() }}
                                    </div>
                                    @if ($vehicle->secondaryPrice())
                                        <div class="text-xs text-neutral-400 tabular-nums">
                                            {{ $vehicle->secondaryPrice() }}
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
