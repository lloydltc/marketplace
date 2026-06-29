<x-layouts.app>
    <x-slot:title>{{ $part->name }}</x-slot:title>
    <x-slot:metaDescription>{{ $part->name }}{{ $part->brand ? ' — ' . $part->brand : '' }}. Compare seller offers on SalmaDrive.</x-slot:metaDescription>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-breadcrumbs class="mb-6" :items="[
            ['label' => 'Parts', 'url' => route('parts.index')],
            ['label' => $part->name],
        ]" />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Left: identity + fitment --}}
            <div class="lg:col-span-2 space-y-6">
                <x-card padding="lg">
                    <div class="flex items-start gap-5">
                        <div class="w-28 h-28 rounded-lg bg-surface-2 flex items-center justify-center overflow-hidden shrink-0">
                            @if ($part->primaryImage())
                                <img src="{{ $part->primaryImage()->url() }}" alt="{{ $part->name }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-4xl text-[rgb(var(--text-muted))]">🔧</span>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-start justify-between gap-3">
                                <div class="text-caption text-[rgb(var(--text-muted))]">{{ $part->brand }} @if($part->brand && $part->category) · @endif {{ $part->category?->name }}</div>
                                <x-part-compare-toggle :part="$part" class="shrink-0" />
                            </div>
                            <h1 class="text-h2 text-ink mt-0.5">{{ $part->name }}</h1>
                            @if ($part->primary_oem)
                                <div class="text-caption font-mono text-[rgb(var(--text-muted))] mt-1">OEM: {{ $part->primary_oem }}</div>
                            @endif

                            {{-- PM3: fits-your-vehicle confirmation / mismatch --}}
                            @if ($fitsSelection === true)
                                <span class="inline-flex items-center gap-1 mt-3 text-caption font-semibold bg-[rgb(var(--success)/0.15)] text-[rgb(var(--success))] px-2 py-0.5 rounded-full">✓ Fits your {{ $context->get()['label'] }}</span>
                            @elseif ($fitsSelection === false)
                                <span class="inline-flex items-center gap-1 mt-3 text-caption font-semibold bg-[rgb(var(--warning)/0.15)] text-[rgb(var(--warning))] px-2 py-0.5 rounded-full">⚠ May not fit your {{ $context->get()['label'] }}</span>
                            @endif
                            @if ($part->is_universal)
                                <span class="inline-flex items-center gap-1 mt-3 text-caption font-medium bg-surface-2 text-[rgb(var(--text-muted))] px-2 py-0.5 rounded-full">Universal fit</span>
                            @endif
                        </div>
                    </div>

                    @if ($part->description)
                        <div class="prose prose-sm max-w-none text-[rgb(var(--text))] mt-5">{!! nl2br(e($part->description)) !!}</div>
                    @endif

                    @if ($part->warranty_months)
                        <div class="mt-5 flex items-center gap-2 text-body-sm">
                            <span class="text-[rgb(var(--success))]">🛡</span>
                            <span class="text-ink font-medium">{{ $part->warranty_months }}-month warranty</span>
                            @if ($part->warranty_terms)<span class="text-[rgb(var(--text-muted))]">· {{ $part->warranty_terms }}</span>@endif
                        </div>
                    @endif

                    @if ($part->guides->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($part->guides as $guide)
                                <a href="{{ $guide->url }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1 text-caption font-medium text-[rgb(var(--info))] hover:underline">
                                    {{ $guide->type === 'video' ? '▶' : '📄' }} {{ $guide->title }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                {{-- Fits these vehicles --}}
                @if ($part->fitments->isNotEmpty() || $part->is_universal)
                    <x-card padding="lg">
                        <h2 class="text-h4 text-ink mb-3">Fits these vehicles</h2>
                        @if ($part->is_universal)
                            <p class="text-body-sm text-[rgb(var(--text-muted))]">Universal — fits all vehicles.</p>
                        @else
                            <div class="flex flex-wrap gap-2">
                                @foreach ($part->fitments as $f)
                                    <span class="text-caption font-medium bg-surface-2 border border-line text-ink px-2.5 py-1 rounded-full">{{ $f->label() }}</span>
                                @endforeach
                            </div>
                        @endif
                    </x-card>
                @endif

                {{-- OEM numbers + guides --}}
                @if ($part->oemNumbers->isNotEmpty())
                    <x-card padding="lg">
                        <h2 class="text-h4 text-ink mb-3">OEM &amp; cross-reference numbers</h2>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($part->oemNumbers as $oem)
                                <span class="text-caption font-mono bg-surface-2 border border-line text-ink px-2.5 py-1 rounded-full">{{ $oem->number }} <span class="text-[rgb(var(--text-muted))]">{{ $oem->type }}</span></span>
                            @endforeach
                        </div>
                    </x-card>
                @endif

                {{-- PM6: service kits including this part --}}
                @if (($kits ?? collect())->isNotEmpty())
                    <x-card padding="lg">
                        <h2 class="text-h4 text-ink mb-3">Service kits with this part</h2>
                        <div class="space-y-2">
                            @foreach ($kits as $kit)
                                <a href="{{ route('bundles.show', $kit->slug) }}" class="flex items-center justify-between gap-3 border border-line rounded-lg p-3 hover:shadow-e1 transition-shadow">
                                    <span class="text-body-sm font-medium text-ink">🧰 {{ $kit->name }}</span>
                                    <span class="text-body-sm font-bold text-ink tabular-nums">USD {{ number_format($kit->effectivePrice(), 2) }}</span>
                                </a>
                            @endforeach
                        </div>
                    </x-card>
                @endif

                {{-- PM5: alternatives (curated + OEM-derived) --}}
                @if ($alternatives->isNotEmpty())
                    <x-card padding="lg">
                        <h2 class="text-h4 text-ink mb-3">Alternatives &amp; substitutes</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach ($alternatives as $alt)
                                <a href="{{ route('parts.show', $alt->slug) }}" class="group flex items-center gap-2 border border-line rounded-lg p-2 hover:shadow-e1 transition-shadow">
                                    <div class="size-10 rounded bg-surface-2 grid place-items-center overflow-hidden shrink-0">
                                        @if ($alt->primaryImage())<img src="{{ $alt->primaryImage()->url() }}" alt="{{ $alt->name }}" class="w-full h-full object-cover">@else<span class="text-[rgb(var(--text-muted))]">🔧</span>@endif
                                    </div>
                                    <span class="text-caption font-medium text-ink line-clamp-2 group-hover:text-brand">{{ $alt->name }}</span>
                                </a>
                            @endforeach
                        </div>
                    </x-card>
                @endif
            </div>

            {{-- Right: seller offers (compare) --}}
            <div>
                <x-card padding="lg">
                    <h2 class="text-h4 text-ink mb-1">Seller offers</h2>
                    <p class="text-caption text-[rgb(var(--text-muted))] mb-4">{{ $offers->count() }} {{ Str::plural('seller', $offers->count()) }} — lowest price first.</p>

                    @if ($offers->isEmpty())
                        <p class="text-body-sm text-[rgb(var(--text-muted))]">No offers right now.</p>
                        <div class="mt-4"><x-rfq-cta context="parts" :query="$part->name" /></div>
                    @else
                        <div class="space-y-3">
                            @foreach ($offers as $i => $offer)
                                <div class="border border-line rounded-lg p-3 {{ $i === 0 ? 'ring-1 ring-[rgb(var(--brand)/0.4)]' : '' }}">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="text-body font-bold text-ink tabular-nums">USD {{ number_format((float) $offer->price_usd, 2) }}</div>
                                            <div class="text-caption text-[rgb(var(--text-muted))] truncate">
                                                {{ $offer->vendor?->name }}
                                                @if ($offer->condition) · <span class="capitalize">{{ $offer->condition }}</span>@endif
                                            </div>
                                        </div>
                                        @if ($i === 0)<span class="shrink-0 text-overline uppercase bg-[rgb(var(--brand)/0.15)] text-brand px-2 py-0.5 rounded-full">Lowest</span>@endif
                                    </div>
                                    <div class="flex items-center justify-between mt-2 text-caption">
                                        <span class="{{ $offer->isInStock() ? 'text-[rgb(var(--success))]' : 'text-[rgb(var(--danger))]' }}">
                                            {{ $offer->isInStock() ? 'In stock' : 'Out of stock' }}
                                        </span>
                                        @if ($offer->isInStock())
                                            <form method="POST" action="{{ route('cart.add') }}">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $offer->id }}">
                                                <x-button type="submit" variant="primary" size="sm">Add to cart</x-button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>
        </div>

        {{-- PM5: frequently bought together (deterministic co-purchase counts, no AI) --}}
        @if ($frequentlyBought->isNotEmpty())
            <section class="mt-12">
                <h2 class="text-h3 text-ink mb-5">Frequently bought together</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
                    @foreach ($frequentlyBought as $fbt)
                        <a href="{{ route('parts.show', $fbt->slug) }}"
                           class="group bg-surface border border-line rounded-xl shadow-e1 hover:shadow-e2 transition-shadow overflow-hidden flex flex-col">
                            <div class="aspect-square bg-surface-2 flex items-center justify-center overflow-hidden">
                                @if ($fbt->primaryImage())<img src="{{ $fbt->primaryImage()->url() }}" alt="{{ $fbt->name }}" class="w-full h-full object-cover">@else<span class="text-4xl text-[rgb(var(--text-muted))]">🔧</span>@endif
                            </div>
                            <div class="p-4">
                                <div class="text-caption text-[rgb(var(--text-muted))] mb-1">{{ $fbt->brand ?? $fbt->category?->name }}</div>
                                <h3 class="text-body-sm font-semibold text-ink line-clamp-2 group-hover:text-brand transition-colors">{{ $fbt->name }}</h3>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- PM10: compatible vehicles for sale (part → vehicle cross-sell) --}}
        @if (($compatibleVehicles ?? collect())->isNotEmpty())
            <section class="mt-12">
                <h2 class="text-h3 text-ink mb-5">Compatible vehicles for sale</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach ($compatibleVehicles as $vehicle)
                        <x-vehicle-card :vehicle="$vehicle" :compare="false" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
