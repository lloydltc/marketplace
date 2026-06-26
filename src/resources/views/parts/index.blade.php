<x-layouts.app>
    <x-slot:title>Parts &amp; Accessories</x-slot:title>
    <x-slot:metaDescription>Find car parts that fit your vehicle on SalmaDrive — search by vehicle, category, or OEM number.</x-slot:metaDescription>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <header class="mb-6">
            <h1 class="text-h1 text-ink">Parts &amp; accessories</h1>
            <p class="text-body-sm text-[rgb(var(--text-muted))] mt-1">Search by your vehicle for guaranteed-fit parts, or browse by category.</p>
        </header>

        {{-- PM3/PM4: fitment selector --}}
        <div class="mb-4">
            <x-fitment-selector />
        </div>

        {{-- PM4: basic VIN search --}}
        <form method="POST" action="{{ route('parts.vin') }}" class="flex gap-2 mb-6">
            @csrf
            <input type="text" name="vin" maxlength="32" placeholder="Or paste your VIN (17 chars)"
                   class="flex-1 sm:max-w-xs border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">
            <x-button type="submit" variant="outline" size="md">Decode VIN</x-button>
        </form>
        @error('vin')<p class="text-caption text-[rgb(var(--danger))] -mt-4 mb-4">{{ $message }}</p>@enderror

        @if ($context->has())
            <div class="flex items-center gap-2 mb-5 text-body-sm">
                <span class="inline-flex items-center gap-1 bg-[rgb(var(--success)/0.15)] text-[rgb(var(--success))] font-medium px-3 py-1 rounded-full">
                    ✓ Showing parts for {{ $context->get()['label'] }}
                </span>
            </div>
        @endif

        <div class="lg:grid lg:grid-cols-[16rem_1fr] lg:gap-8">
            {{-- Facets --}}
            <aside class="mb-6 lg:mb-0">
                <form method="GET" class="bg-surface border border-line rounded-xl p-4 space-y-4">
                    <div>
                        <label class="block text-overline uppercase text-[rgb(var(--text-muted))] mb-1">Search</label>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Name, brand, OEM…"
                               class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">
                    </div>
                    <div>
                        <label class="block text-overline uppercase text-[rgb(var(--text-muted))] mb-1">Category</label>
                        <select name="category" class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                            <option value="">All categories</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(request('category') === $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($brands->isNotEmpty())
                        <div>
                            <label class="block text-overline uppercase text-[rgb(var(--text-muted))] mb-1">Brand</label>
                            <select name="brand" class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                                <option value="">All brands</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand }}" @selected(request('brand') === $brand)>{{ $brand }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <label class="flex items-center gap-2 text-body-sm text-ink">
                        <input type="checkbox" name="in_stock" value="1" @checked(request('in_stock')) class="rounded border-line text-brand">
                        In stock only
                    </label>
                    <div>
                        <label class="block text-overline uppercase text-[rgb(var(--text-muted))] mb-1">Sort</label>
                        <select name="sort" class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                            <option value="latest" @selected(request('sort', 'latest') === 'latest')>Latest</option>
                            <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: low to high</option>
                            <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: high to low</option>
                            <option value="name" @selected(request('sort') === 'name')>Name</option>
                        </select>
                    </div>
                    <x-button type="submit" variant="primary" size="md" class="w-full">Apply</x-button>
                </form>
            </aside>

            {{-- Results --}}
            <div>
                <p class="text-body-sm text-[rgb(var(--text-muted))] mb-4 tabular-nums">
                    {{ number_format($parts->total()) }} {{ Str::plural('part', $parts->total()) }} found
                </p>

                @if ($parts->isEmpty())
                    <div class="py-12 space-y-6">
                        <p class="text-center text-[rgb(var(--text-muted))]">No parts match{{ $context->has() ? ' for ' . $context->get()['label'] : '' }}.</p>
                        <x-rfq-cta context="parts" :query="request('q', '')" />
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
                        @foreach ($parts as $part)
                            <a href="{{ route('parts.show', $part->slug) }}"
                               class="group bg-surface border border-line rounded-xl shadow-e1 hover:shadow-e2 transition-shadow overflow-hidden flex flex-col">
                                <div class="aspect-square bg-surface-2 flex items-center justify-center overflow-hidden">
                                    @if ($part->primaryImage())
                                        <img src="{{ $part->primaryImage()->url() }}" alt="{{ $part->name }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-4xl text-[rgb(var(--text-muted))]">🔧</span>
                                    @endif
                                </div>
                                <div class="p-4 flex flex-col flex-1">
                                    @if ($context->has())
                                        <span class="self-start mb-2 text-caption font-semibold bg-[rgb(var(--success)/0.15)] text-[rgb(var(--success))] px-2 py-0.5 rounded-full">✓ Fits</span>
                                    @endif
                                    <div class="text-caption text-[rgb(var(--text-muted))] mb-1">{{ $part->brand ?? $part->category?->name }}</div>
                                    <h3 class="text-body-sm font-semibold text-ink line-clamp-2 group-hover:text-brand transition-colors">{{ $part->name }}</h3>
                                    <div class="mt-auto pt-3 text-body font-bold text-ink tabular-nums">
                                        @if ($part->price_from !== null)
                                            from USD {{ number_format((float) $part->price_from, 2) }}
                                        @else
                                            <span class="text-[rgb(var(--text-muted))] text-body-sm">See offers</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <div class="mt-8">{{ $parts->withQueryString()->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
