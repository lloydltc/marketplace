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
                            <div class="text-caption text-[rgb(var(--text-muted))]">{{ $part->brand }} @if($part->brand && $part->category) · @endif {{ $part->category?->name }}</div>
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

                {{-- PM5: alternatives + frequently-bought-together added here --}}
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
    </div>
</x-layouts.app>
