<x-layouts.app>
    <x-slot:title>{{ $vendor->name }}</x-slot:title>
    <x-slot:metaDescription>{{ $vendor->name }} on SalmaDrive — {{ $vendor->description ? Str::limit($vendor->description, 140) : 'verified dealer inventory.' }}</x-slot:metaDescription>

    {{-- Storefront header: banner + logo + name + verified tier + location + stats --}}
    <section class="relative overflow-hidden bg-sidebar">
        <img src="{{ asset('banner/banner2.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-20" aria-hidden="true">
        <div class="absolute inset-0 bg-gradient-to-t from-[rgb(var(--bg-sidebar))] via-[rgb(var(--bg-sidebar)/0.85)] to-[rgb(var(--bg-sidebar)/0.6)]"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <a href="{{ route('dealers.index') }}" class="text-body-sm text-neutral-400 hover:text-white transition-colors">← All dealers</a>

            <div class="flex flex-col sm:flex-row sm:items-end gap-5 mt-4">
                <div class="size-20 rounded-xl bg-white/10 ring-1 ring-white/15 grid place-items-center overflow-hidden shrink-0">
                    @if ($vendor->logoUrl())
                        <img src="{{ $vendor->logoUrl() }}" alt="{{ $vendor->name }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-h1 font-bold text-white/60">{{ Str::upper(Str::substr($vendor->name, 0, 1)) }}</span>
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-h1 font-bold text-white">{{ $vendor->name }}</h1>
                        <span class="inline-flex items-center gap-1 text-caption font-semibold bg-[rgb(var(--success)/0.2)] text-[rgb(var(--success))] px-2 py-0.5 rounded-full">✓ Verified dealer</span>
                        {{-- VB5: trust tier badge --}}
                        <x-trust-badge :vendor="$vendor" />
                        @if ($vendor->isFeaturedDealer())
                            <span class="inline-flex items-center gap-1 text-caption font-semibold bg-[rgb(var(--brand)/0.2)] text-brand px-2 py-0.5 rounded-full">★ Featured</span>
                        @endif
                    </div>

                    @if ($vendor->address)
                        <p class="mt-2 text-body-sm text-neutral-400 inline-flex items-center gap-1">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-6-5.3-6-10a6 6 0 1112 0c0 4.7-6 10-6 10z"/><circle cx="12" cy="11" r="2"/></svg>
                            {{ $vendor->address }}
                        </p>
                    @endif

                    <div class="mt-4 flex items-center gap-6">
                        <div><span class="text-h3 font-bold text-white tabular-nums">{{ number_format($vehicles->total()) }}</span> <span class="text-caption text-neutral-400">vehicles</span></div>
                        <div class="w-px h-8 bg-white/15"></div>
                        <div><span class="text-h3 font-bold text-white tabular-nums">{{ number_format($products->total()) }}</span> <span class="text-caption text-neutral-400">parts</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-tabs :tabs="['listings' => 'Listings', 'about' => 'About', 'contact' => 'Contact']" default="listings">

            {{-- Listings --}}
            <div x-show="tab === 'listings'" class="space-y-12">
                <section>
                    <h2 class="text-h2 text-ink mb-5">Vehicles ({{ number_format($vehicles->total()) }})</h2>
                    @if ($vehicles->isEmpty())
                        <x-empty title="No vehicles listed right now" message="Check back soon — this dealer updates inventory regularly." />
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            @foreach ($vehicles as $vehicle)
                                <x-vehicle-card :vehicle="$vehicle" :compare="false" />
                            @endforeach
                        </div>
                        <x-pagination :paginator="$vehicles->withQueryString()" class="mt-6" />
                    @endif
                </section>

                <section>
                    <h2 class="text-h2 text-ink mb-5">Parts &amp; accessories ({{ number_format($products->total()) }})</h2>
                    @if ($products->isEmpty())
                        <x-empty title="No parts listed right now" message="This dealer hasn't stocked parts yet." />
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
                            @foreach ($products as $product)
                                <x-part-card :product="$product" />
                            @endforeach
                        </div>
                        <x-pagination :paginator="$products->withQueryString()" class="mt-6" />
                    @endif
                </section>
            </div>

            {{-- About --}}
            <div x-show="tab === 'about'" x-cloak>
                <x-card padding="lg" class="max-w-3xl">
                    <h2 class="text-h3 text-ink mb-3">About {{ $vendor->name }}</h2>
                    @if ($vendor->description)
                        <p class="text-body text-[rgb(var(--text))] whitespace-pre-line leading-relaxed">{{ $vendor->description }}</p>
                    @else
                        <p class="text-body-sm text-muted">This dealer hasn't added a description yet.</p>
                    @endif
                </x-card>
            </div>

            {{-- Contact --}}
            <div x-show="tab === 'contact'" x-cloak>
                <x-card padding="lg" class="max-w-md space-y-3">
                    <h2 class="text-h3 text-ink mb-1">Get in touch</h2>
                    @if ($vendor->phone)
                        <a href="tel:{{ $vendor->phone }}" class="flex items-center gap-2 text-body-sm text-[rgb(var(--text))] hover:text-brand transition-colors">
                            <svg class="size-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h2.3a1 1 0 01.95.68l1 3a1 1 0 01-.5 1.2L7.2 9.1a12 12 0 005.7 5.7l1.2-1.55a1 1 0 011.2-.5l3 1a1 1 0 01.68.95V17a2 2 0 01-2 2A14 14 0 013 5z"/></svg>
                            {{ $vendor->phone }}
                        </a>
                    @endif
                    @if ($vendor->address)
                        <p class="flex items-start gap-2 text-body-sm text-[rgb(var(--text))]">
                            <svg class="size-4 text-muted mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-6-5.3-6-10a6 6 0 1112 0c0 4.7-6 10-6 10z"/><circle cx="12" cy="11" r="2"/></svg>
                            {{ $vendor->address }}
                        </p>
                    @endif
                    @if (! $vendor->phone && ! $vendor->address)
                        <p class="text-body-sm text-muted">Contact details aren't published. Enquire on any listing to reach this dealer.</p>
                    @endif
                </x-card>
            </div>

        </x-tabs>
    </div>
</x-layouts.app>
