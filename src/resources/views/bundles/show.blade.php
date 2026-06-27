<x-layouts.app>
    <x-slot:title>{{ $bundle->name }}</x-slot:title>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-breadcrumbs class="mb-6" :items="[
            ['label' => 'Parts', 'url' => route('parts.index')],
            ['label' => $bundle->name],
        ]" />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                <x-card padding="lg">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-2xl">🧰</span>
                        <h1 class="text-h2 text-ink">{{ $bundle->name }}</h1>
                    </div>
                    @if ($bundle->is_service_kit)
                        <x-badge variant="neutral">Service kit</x-badge>
                    @endif
                    @if ($bundle->description)
                        <div class="prose prose-sm max-w-none text-[rgb(var(--text))] mt-4">{!! nl2br(e($bundle->description)) !!}</div>
                    @endif
                </x-card>

                <x-card padding="lg">
                    <h2 class="text-h4 text-ink mb-3">What's in this kit</h2>
                    <ul class="divide-y divide-[rgb(var(--border))]">
                        @foreach ($bundle->items as $item)
                            <li class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <span class="text-body-sm font-medium text-ink">{{ $item->qty }}× {{ $item->product?->title }}</span>
                                    <div class="text-caption text-[rgb(var(--text-muted))]">{{ $item->product?->vendor?->name }}</div>
                                </div>
                                <span class="text-body-sm tabular-nums text-ink">USD {{ number_format((float) ($item->product?->price_usd ?? 0) * $item->qty, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            </div>

            <div>
                <x-card padding="lg">
                    <div class="text-overline uppercase text-[rgb(var(--text-muted))]">Kit price</div>
                    <div class="text-h2 text-ink tabular-nums my-1">USD {{ number_format($bundle->effectivePrice(), 2) }}</div>
                    <p class="text-caption {{ $bundle->isInStock() ? 'text-[rgb(var(--success))]' : 'text-[rgb(var(--danger))]' }} mb-4">
                        {{ $bundle->isInStock() ? 'All items in stock' : 'Some items out of stock' }}
                    </p>
                    @if ($bundle->isInStock())
                        <form method="POST" action="{{ route('bundles.add', $bundle) }}">
                            @csrf
                            <x-button type="submit" variant="primary" size="lg" class="w-full">Add kit to cart</x-button>
                        </form>
                    @endif
                    @error('cart')<p class="text-caption text-[rgb(var(--danger))] mt-2">{{ $message }}</p>@enderror
                </x-card>
            </div>
        </div>
    </div>
</x-layouts.app>
