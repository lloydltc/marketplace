<x-layouts.app>
    <x-slot:title>SalmaDrive — Find It. Buy It. Drive It.</x-slot:title>

    {{-- ── Hero ─────────────────────────────────────────────────────────────
         Always dark (uses --bg-sidebar so it holds in light AND dark mode),
         with a banner photo behind a gradient scrim for legibility. --}}
    <section class="relative overflow-hidden bg-[rgb(var(--bg-sidebar))]">
        <img src="{{ asset('banner/banner1.png') }}" alt=""
             class="absolute inset-0 w-full h-full object-cover opacity-30" aria-hidden="true">
        <div class="absolute inset-0 bg-gradient-to-t from-[rgb(var(--bg-sidebar))] via-[rgb(var(--bg-sidebar)/0.78)] to-[rgb(var(--bg-sidebar)/0.55)]"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-20 sm:pt-24 sm:pb-28 text-center">
            <p class="text-brand text-overline uppercase mb-4">Zimbabwe's trusted automotive marketplace</p>
            <h1 class="font-display text-display-lg sm:text-display-2xl font-bold text-white leading-[1.05] tracking-tight mb-5">
                Find It. <span class="text-brand">Buy It.</span> Drive It.
            </h1>
            <p class="text-body-lg text-white/70 max-w-xl mx-auto mb-9">
                Browse vehicles, parts, and accessories from verified dealers and private sellers.
            </p>

            {{-- Pill search → unified results --}}
            <form method="GET" action="{{ route('search.index') }}"
                  class="w-full max-w-2xl mx-auto bg-surface p-2 rounded-2xl sm:rounded-full shadow-e4 flex flex-col sm:flex-row items-center gap-2">
                <div class="flex-1 flex items-center gap-3 px-4 w-full">
                    <svg class="size-5 text-[rgb(var(--text-muted))] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                    </svg>
                    <label for="hero-q" class="sr-only">Search vehicles and parts</label>
                    <input id="hero-q" type="text" name="q" placeholder="Search make, model, or part…"
                           class="w-full h-11 bg-transparent text-[rgb(var(--text-strong))] placeholder:text-[rgb(var(--text-muted))] text-body-sm focus-visible:outline-none">
                </div>
                <x-button type="submit" size="lg" class="w-full sm:w-auto !rounded-xl sm:!rounded-full">Search</x-button>
            </form>

            {{-- Type tabs → deep-link to filtered results --}}
            <div class="flex flex-wrap items-center justify-center gap-2.5 mt-10">
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center gap-2 bg-white/10 hover:bg-white border border-white/10 hover:border-white text-white hover:text-[rgb(var(--bg-sidebar))] text-body-sm font-medium px-5 py-2.5 rounded-full backdrop-blur-sm transition-colors">
                    <span aria-hidden="true">🔧</span> Parts
                </a>
                @foreach (config('vehicle_types.types') as $key => $cfg)
                    @php $tc = $typeCounts[$key] ?? 0; @endphp
                    <a href="{{ route('vehicles.index', ['vehicle_type' => $key]) }}"
                       class="group inline-flex items-center gap-2 bg-white/10 hover:bg-white border border-white/10 hover:border-white text-white hover:text-[rgb(var(--bg-sidebar))] text-body-sm font-medium px-5 py-2.5 rounded-full backdrop-blur-sm transition-colors">
                        <span aria-hidden="true">{{ $cfg['icon'] }}</span> {{ $cfg['plural'] }}
                        @if ($tc > 0)<span class="text-white/50 group-hover:text-[rgb(var(--text-muted))] text-caption tabular-nums transition-colors">{{ number_format($tc) }}</span>@endif
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── Browse by make ───────────────────────────────────────────────── --}}
    @if ($popularMakes->isNotEmpty())
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-h2 text-[rgb(var(--text-strong))]">Browse by make</h2>
                <a href="{{ route('vehicles.index') }}" class="text-body-sm font-semibold text-[rgb(var(--info))] hover:underline">All makes →</a>
            </div>
            <div class="flex items-center gap-2.5 overflow-x-auto sd-rail pb-1">
                @foreach ($popularMakes as $make)
                    <a href="{{ route('vehicles.index', ['make_id' => $make->id]) }}"
                       class="shrink-0 inline-flex items-center gap-1.5 bg-surface border border-base hover:border-brand text-[rgb(var(--text))] hover:text-brand text-body-sm font-medium px-5 py-2 rounded-full transition-colors">
                        {{ $make->name }}
                        <span class="text-[rgb(var(--text-muted))] text-caption tabular-nums">{{ number_format($make->total) }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ── Featured dealers ─────────────────────────────────────────────── --}}
    @if ($featuredDealers->isNotEmpty())
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14">
            <div class="flex items-end justify-between mb-5">
                <div>
                    <h2 class="text-h2 text-[rgb(var(--text-strong))] flex items-center gap-2">
                        Featured dealers
                        <x-badge variant="featured" :icon="false">Sponsored</x-badge>
                    </h2>
                    <p class="text-body-sm text-[rgb(var(--text-muted))] mt-1">Trusted partners with verified inventory.</p>
                </div>
                <a href="{{ route('dealers.index') }}" class="hidden sm:inline-flex text-body-sm font-semibold text-[rgb(var(--info))] hover:underline">Find a dealer →</a>
            </div>
            <div class="flex gap-5 overflow-x-auto sd-rail pb-2 snap-x">
                @foreach ($featuredDealers as $dealer)
                    <x-dealer-card :dealer="$dealer" :featured="true" class="snap-start shrink-0 w-72" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- ── Featured listings — vehicle-card grid ────────────────────────── --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14">
        <div class="flex items-end justify-between mb-6">
            <div>
                <h2 class="text-h2 text-[rgb(var(--text-strong))]">Vehicles for sale</h2>
                <p class="text-body-sm text-[rgb(var(--text-muted))] mt-1">Latest listings near you.</p>
            </div>
            <a href="{{ route('vehicles.index') }}" class="hidden sm:inline-flex text-body-sm font-semibold text-[rgb(var(--info))] hover:underline">View all vehicles →</a>
        </div>

        @if (empty($vehicles))
            <x-empty title="No vehicles listed yet" message="Check back soon — new listings arrive daily." />
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($vehicles as $vehicle)
                    <x-vehicle-card :vehicle="$vehicle" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- ── Sponsored + recently viewed rails ────────────────────────────── --}}
    <x-vehicle-row title="Sponsored listings" :vehicles="$sponsored" :sponsored="true" :view-all="route('vehicles.index')" />
    <x-vehicle-row title="Recently viewed" :vehicles="$recentlyViewed" />

    {{-- ── Parts spotlight ──────────────────────────────────────────────── --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 pb-16">
        <div class="flex items-end justify-between mb-6">
            <div>
                <h2 class="text-h2 text-[rgb(var(--text-strong))]">Parts &amp; accessories</h2>
                <p class="text-body-sm text-[rgb(var(--text-muted))] mt-1">Keep it running. Make it yours.</p>
            </div>
            <a href="{{ route('products.index') }}" class="hidden sm:inline-flex text-body-sm font-semibold text-[rgb(var(--info))] hover:underline">Shop parts →</a>
        </div>

        @if (empty($products))
            <x-empty title="No parts listed yet" message="Vendors are stocking up — check back soon." />
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach ($products as $product)
                    <x-part-card :product="$product" />
                @endforeach
            </div>
        @endif
    </section>

</x-layouts.app>
