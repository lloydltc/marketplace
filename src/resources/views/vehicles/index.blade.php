<x-layouts.app>
    <x-slot:title>Vehicles for sale</x-slot:title>

    @php
        // Active-filter chips — each maps to a label and a "remove" URL.
        $makeName = request('make_id') ? optional($makes->firstWhere('id', request('make_id')))->name : null;
        $chips = collect([
            ['key' => 'search',       'label' => request('search') ? 'Search: ' . request('search') : null],
            ['key' => 'make_id',      'label' => $makeName ? 'Make: ' . $makeName : null],
            ['key' => 'body_type',    'label' => request('body_type') ? 'Body: ' . ucfirst(str_replace('_', ' ', request('body_type'))) : null],
            ['key' => 'transmission', 'label' => request('transmission') ? 'Transmission: ' . ucfirst(request('transmission')) : null],
            ['key' => 'fuel_type',    'label' => request('fuel_type') ? 'Fuel: ' . ucfirst(request('fuel_type')) : null],
            ['key' => 'condition',    'label' => request('condition') ? 'Condition: ' . ucfirst(request('condition')) : null],
            ['key' => 'year_min',     'label' => request('year_min') ? 'Year ≥ ' . request('year_min') : null],
            ['key' => 'year_max',     'label' => request('year_max') ? 'Year ≤ ' . request('year_max') : null],
            ['key' => 'min_price',    'label' => request('min_price') ? 'Min $' . number_format((int) request('min_price')) : null],
            ['key' => 'max_price',    'label' => request('max_price') ? 'Max $' . number_format((int) request('max_price')) : null],
        ])->filter(fn ($c) => $c['label']);
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <header class="mb-6">
            <h1 class="text-h1 text-[rgb(var(--text-strong))]">
                {{ request('vehicle_type') ? config('vehicle_types.types.' . request('vehicle_type') . '.plural', 'Vehicles') . ' for sale' : 'Vehicles for sale' }}
            </h1>
            <p class="text-body-sm text-[rgb(var(--text-muted))] mt-1">Browse from dealers and private sellers.</p>
        </header>

        {{-- Listing-type tabs (H0/H6) --}}
        @include('partials.vehicle-type-tabs')

        {{-- Browse by body type (H6) --}}
        @if (! empty($bodyTypeCounts))
            <div class="flex flex-wrap gap-2 mb-6">
                @foreach ($bodyTypeCounts as $body => $count)
                    <a href="{{ route('vehicles.index', array_merge(request()->except(['page']), ['body_type' => $body])) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-caption font-medium transition-colors {{ request('body_type') === $body ? 'bg-[rgb(var(--brand)/0.15)] text-brand border border-[rgb(var(--brand)/0.4)]' : 'bg-surface border border-line text-[rgb(var(--text-muted))] hover:border-strong' }}">
                        <span class="capitalize">{{ str_replace('_', ' ', $body) }}</span>
                        <span class="text-[rgb(var(--text-muted))] tabular-nums">{{ number_format($count) }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        <div x-data="{ filtersOpen: false }" class="lg:grid lg:grid-cols-[18rem_1fr] lg:gap-8">

            {{-- Filter rail (lg+) / bottom-sheet (mobile) --}}
            <div>
                {{-- mobile backdrop --}}
                <div x-show="filtersOpen" x-cloak x-transition.opacity @click="filtersOpen = false"
                     class="lg:hidden fixed inset-0 bg-black/50 z-drawer"></div>

                <aside
                    class="lg:sticky lg:top-20 lg:max-h-[calc(100vh-6rem)] lg:overflow-y-auto sd-rail lg:bg-transparent lg:p-0 lg:shadow-none lg:rounded-none lg:translate-y-0
                           fixed inset-x-0 bottom-0 z-drawer max-h-[85vh] overflow-y-auto bg-surface rounded-t-2xl p-5 shadow-e4 transition-transform duration-300 ease-standard"
                    :class="filtersOpen ? 'translate-y-0' : 'translate-y-full lg:translate-y-0'"
                    role="dialog" aria-label="Filters">
                    <div class="flex items-center justify-between mb-4 lg:mb-3">
                        <h2 class="text-h3 text-[rgb(var(--text-strong))]">Filters</h2>
                        <x-icon-button label="Close filters" class="lg:hidden" @click="filtersOpen = false">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" /></svg>
                        </x-icon-button>
                    </div>
                    @include('partials.vehicle-filters')
                </aside>
            </div>

            {{-- Results column --}}
            <div>
                {{-- Result bar: count · sort · mobile filters trigger --}}
                <div class="flex items-center justify-between gap-3 mb-4">
                    <p class="text-body-sm text-[rgb(var(--text-muted))] tabular-nums">
                        @if ($vehicles->total() > 0)
                            {{ number_format($vehicles->total()) }} {{ Str::plural('vehicle', $vehicles->total()) }} found
                        @else
                            No vehicles found
                        @endif
                    </p>

                    <div class="flex items-center gap-2">
                        {{-- Sort: own GET form, preserves current filters --}}
                        <form method="GET" action="{{ route('vehicles.index') }}" class="w-40 sm:w-48">
                            @foreach (request()->except(['sort', 'page']) as $k => $v)
                                @if (is_array($v))
                                    @foreach ($v as $kk => $vv)<input type="hidden" name="{{ $k }}[{{ $kk }}]" value="{{ $vv }}">@endforeach
                                @else
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endif
                            @endforeach
                            <label for="sort" class="sr-only">Sort</label>
                            <x-select id="sort" name="sort" onchange="this.form.submit()">
                                <option value="latest" @selected(request('sort', 'latest') === 'latest')>Latest</option>
                                <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: low to high</option>
                                <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: high to low</option>
                                <option value="year_desc" @selected(request('sort') === 'year_desc')>Year: newest</option>
                                <option value="mileage_asc" @selected(request('sort') === 'mileage_asc')>Mileage: lowest</option>
                            </x-select>
                        </form>

                        <x-button variant="outline" size="md" class="lg:hidden" @click="filtersOpen = true">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M6 8h12M9 12h6M11 16h2" /></svg>
                            Filters
                        </x-button>
                    </div>
                </div>

                {{-- Active-filter chips --}}
                @if ($chips->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-2 mb-5">
                        @foreach ($chips as $chip)
                            <a href="{{ route('vehicles.index', request()->except([$chip['key'], 'page'])) }}"
                               class="inline-flex items-center gap-1.5 rounded-full bg-surface-2 px-3 py-1 text-caption text-[rgb(var(--text))] hover:bg-[rgb(var(--border))] transition-colors">
                                {{ $chip['label'] }}
                                <span aria-hidden="true" class="text-[rgb(var(--text-muted))]">✕</span>
                                <span class="sr-only">Remove filter</span>
                            </a>
                        @endforeach
                        <a href="{{ route('vehicles.index', request('vehicle_type') ? ['vehicle_type' => request('vehicle_type')] : []) }}"
                           class="text-caption font-medium text-[rgb(var(--info))] hover:underline px-1">Clear all</a>
                    </div>
                @endif

                @if ($vehicles->isEmpty())
                    <div class="py-10 space-y-6">
                        <x-empty title="No vehicles match your search" message="Try widening your filters, or let sellers come to you." />
                        <x-rfq-cta context="vehicles" :query="request('search', '')" />
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                        @foreach ($vehicles as $vehicle)
                            <x-vehicle-card :vehicle="$vehicle" />
                        @endforeach
                    </div>

                    <x-pagination :paginator="$vehicles->withQueryString()" class="mt-8" />
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
