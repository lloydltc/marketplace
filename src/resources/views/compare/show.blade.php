<x-layouts.app>
    <x-slot:title>Compare vehicles</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-h1 text-ink">Compare vehicles</h1>
                <p class="text-body-sm text-muted mt-1">Up to {{ config('engagement.compare.max_items') }} side by side — differences are highlighted.</p>
            </div>
            @if ($vehicles->isNotEmpty())
                <form method="POST" action="{{ route('compare.clear') }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-body-sm text-muted hover:text-[rgb(var(--text))] transition-colors">Clear all</button>
                </form>
            @endif
        </div>

        @if ($vehicles->isEmpty())
            <x-empty title="No vehicles to compare yet" message="Add vehicles from any listing to line them up side by side.">
                <x-button :href="route('vehicles.index')">Browse vehicles</x-button>
            </x-empty>
        @else
            @php
                $rows = [
                    ['Price', fn ($v) => $v->primaryPrice()],
                    ['Year', fn ($v) => (string) $v->year],
                    ['Mileage', fn ($v) => number_format($v->mileage) . ' km'],
                    ['Body type', fn ($v) => ucfirst((string) $v->body_type)],
                    ['Transmission', fn ($v) => ucfirst((string) $v->transmission)],
                    ['Fuel', fn ($v) => ucfirst((string) $v->fuel_type)],
                    ['Condition', fn ($v) => ucfirst((string) $v->condition)],
                    ['Steering', fn ($v) => $v->steering ? strtoupper($v->steering) : '—'],
                    ['Seller', fn ($v) => $v->isListedByVendor() ? 'Dealer' : 'Private'],
                ];
            @endphp

            <div class="overflow-x-auto bg-surface border border-line rounded-xl shadow-e1">
                <table class="w-full text-body-sm border-collapse">
                    <thead>
                        <tr class="border-b border-line">
                            <th class="sticky left-0 z-10 bg-surface px-5 py-4 text-left text-overline uppercase text-muted w-36">Listing</th>
                            @foreach ($vehicles as $v)
                                <th class="px-5 py-4 text-left align-top min-w-[200px]">
                                    <a href="{{ route('vehicles.show', $v) }}" class="block group">
                                        <div class="aspect-video bg-surface-2 rounded-lg overflow-hidden mb-2 grid place-items-center">
                                            <x-listing-thumbnail :cover="$v->coverImage()" :alt="$v->displayTitle()" type="vehicle" />
                                        </div>
                                        <span class="font-semibold text-ink group-hover:text-brand transition-colors">{{ $v->displayTitle() }}</span>
                                    </a>
                                    <form method="POST" action="{{ route('compare.remove', $v) }}" class="mt-2">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-caption text-muted hover:text-[rgb(var(--danger))] transition-colors">Remove</button>
                                    </form>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as [$label, $resolver])
                            @php
                                $values = $vehicles->map($resolver)->all();
                                $differs = count(array_unique($values)) > 1;
                            @endphp
                            <tr class="border-t border-line">
                                <td class="sticky left-0 z-10 bg-surface px-5 py-3 text-overline uppercase text-muted">{{ $label }}</td>
                                @foreach ($values as $value)
                                    <td class="px-5 py-3 tabular-nums {{ $differs ? 'bg-[rgb(var(--brand)/0.08)] font-medium text-ink' : 'text-[rgb(var(--text))]' }}">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.app>
