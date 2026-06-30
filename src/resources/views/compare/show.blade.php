<x-layouts.app>
    <x-slot:title>Compare vehicles</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-h1 text-ink">Compare vehicles</h1>
                <p class="text-body-sm text-muted mt-1">Up to {{ config('engagement.compare.max_items') }} side by side — differences are highlighted.</p>
            </div>
            @if ($vehicles->isNotEmpty())
                <div class="flex items-center gap-3 print:hidden">
                    <a href="{{ route('compare.show', ['v' => $vehicles->pluck('id')->implode(',')]) }}"
                       class="text-body-sm text-[rgb(var(--info))] hover:underline">Share</a>
                    <button type="button" onclick="window.print()" class="text-body-sm text-[rgb(var(--info))] hover:underline">Print</button>
                    <form method="POST" action="{{ route('compare.clear') }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-body-sm text-muted hover:text-[rgb(var(--text))] transition-colors">Clear all</button>
                    </form>
                </div>
            @endif
        </div>

        @if ($vehicles->isEmpty())
            <x-empty title="No vehicles to compare yet" message="Add vehicles from any listing to line them up side by side.">
                <x-button :href="route('vehicles.index')">Browse vehicles</x-button>
            </x-empty>
        @else
            @php
                $fc = config('engagement.compare.fuel_cost');
                $fuelCost = function ($v) use ($fc) {
                    $lpk = $fc['l_per_100km'][$v->fuel_type] ?? $fc['l_per_100km']['other'];
                    $cost = ($fc['annual_km'] / 100) * $lpk * $fc['price_per_litre_usd'] * $fc['years'];
                    return $lpk > 0 ? 'USD ' . number_format($cost, 0) : 'N/A (electric)';
                };
                $rows = [
                    ['Price', fn ($v) => $v->primaryPrice()],
                    ['Year', fn ($v) => (string) $v->year],
                    ['Mileage', fn ($v) => number_format($v->mileage) . ' km'],
                    ['Body type', fn ($v) => ucfirst((string) $v->body_type)],
                    ['Transmission', fn ($v) => ucfirst((string) $v->transmission)],
                    ['Fuel', fn ($v) => ucfirst((string) $v->fuel_type)],
                    ['Engine', fn ($v) => $v->engine_cc ? number_format($v->engine_cc) . ' cc' : '—'],
                    ['Est. ' . $fc['years'] . '-yr fuel', $fuelCost],
                    ['Condition', fn ($v) => ucfirst((string) $v->condition)],
                    ['Steering', fn ($v) => $v->steering ? strtoupper($v->steering) : '—'],
                    ['Seller', fn ($v) => $v->isListedByVendor() ? 'Dealer' : 'Private'],
                ];
            @endphp

            <x-compare-table
                :items="$vehicles"
                :rows="$rows"
                type="vehicle"
                :title="fn ($v) => $v->displayTitle()"
                :href="fn ($v) => route('vehicles.show', $v)"
                :image="fn ($v) => $v->coverImage()"
                :remove-action="fn ($v) => route('compare.remove', $v)"
            />

            <p class="text-caption text-muted mt-3">Fuel estimate: {{ number_format(config('engagement.compare.fuel_cost.annual_km')) }} km/yr at USD {{ number_format(config('engagement.compare.fuel_cost.price_per_litre_usd'), 2) }}/L — indicative only.</p>
        @endif
    </div>
</x-layouts.app>
