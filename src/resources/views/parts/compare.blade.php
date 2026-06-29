<x-layouts.app>
    <x-slot:title>Compare Parts</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-h1 text-ink">Compare parts</h1>
                <p class="text-body-sm text-[rgb(var(--text-muted))] mt-1">Up to {{ config('parts.compare_max') }} side by side.</p>
            </div>
            @if ($parts->isNotEmpty())
                <form method="POST" action="{{ route('parts.compare.clear') }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-body-sm text-[rgb(var(--text-muted))] hover:text-ink">Clear all</button>
                </form>
            @endif
        </div>

        @if ($parts->isEmpty())
            <x-card padding="lg" class="text-center py-16">
                <p class="text-[rgb(var(--text-muted))]">No parts to compare yet.</p>
                <a href="{{ route('parts.index') }}" class="inline-block mt-4 text-body-sm font-semibold text-[rgb(var(--info))] hover:underline">Browse parts →</a>
            </x-card>
        @else
            @php
                $rows = [
                    ['Price from', fn ($p) => $p->price_from !== null ? 'USD ' . number_format((float) $p->price_from, 2) : '—'],
                    ['Brand', fn ($p) => $p->brand ?: '—'],
                    ['Category', fn ($p) => $p->category?->name ?: '—'],
                    ['Primary OEM', fn ($p) => $p->primary_oem ?: '—'],
                    ['OEM numbers', fn ($p) => $p->oemNumbers->count()],
                    ['Warranty', fn ($p) => $p->warranty_months ? $p->warranty_months . ' months' : '—'],
                    ['Fitments', fn ($p) => $p->is_universal ? 'Universal' : $p->fitments->count() . ' vehicles'],
                ];
            @endphp

            <div class="overflow-x-auto bg-surface border border-line rounded-xl shadow-e1">
                <table class="w-full text-body-sm">
                    <thead>
                        <tr class="border-b border-line">
                            <th class="px-5 py-4 text-left text-overline uppercase text-[rgb(var(--text-muted))] w-36">Part</th>
                            @foreach ($parts as $p)
                                <th class="px-5 py-4 text-left align-top min-w-[180px]">
                                    <a href="{{ route('parts.show', $p->slug) }}" class="block">
                                        <div class="aspect-square w-20 bg-surface-2 rounded-lg overflow-hidden mb-2 grid place-items-center">
                                            @if ($p->primaryImage())<img src="{{ $p->primaryImage()->url() }}" alt="{{ $p->name }}" class="w-full h-full object-cover">@else<span class="text-2xl text-[rgb(var(--text-muted))]">🔧</span>@endif
                                        </div>
                                        <span class="font-semibold text-ink hover:text-brand">{{ $p->name }}</span>
                                    </a>
                                    <form method="POST" action="{{ route('parts.compare.remove', $p) }}" class="mt-2">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-caption text-[rgb(var(--text-muted))] hover:text-[rgb(var(--danger))]">Remove</button>
                                    </form>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($rows as [$label, $resolver])
                            <tr>
                                <td class="px-5 py-3 text-overline uppercase text-[rgb(var(--text-muted))]">{{ $label }}</td>
                                @foreach ($parts as $p)
                                    <td class="px-5 py-3 text-ink tabular-nums">{{ $resolver($p) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.app>
