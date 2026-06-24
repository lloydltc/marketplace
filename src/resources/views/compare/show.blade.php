<x-layouts.app>
    <x-slot:title>Compare Vehicles</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900">Compare vehicles</h1>
                <p class="text-sm text-neutral-500 mt-1">Up to {{ config('engagement.compare.max_items') }} side by side.</p>
            </div>
            @if ($vehicles->isNotEmpty())
                <form method="POST" action="{{ route('compare.clear') }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm text-neutral-500 hover:text-neutral-700">Clear all</button>
                </form>
            @endif
        </div>

        @if ($vehicles->isEmpty())
            <div class="bg-white border border-neutral-200 rounded-xl py-16 text-center">
                <p class="text-neutral-500">No vehicles to compare yet.</p>
                <a href="{{ route('vehicles.index') }}" class="inline-block mt-4 text-sm font-medium text-[#3DB8E8] hover:underline">Browse vehicles →</a>
            </div>
        @else
            @php
                $rows = [
                    ['Price', fn ($v) => $v->primaryPrice()],
                    ['Year', fn ($v) => $v->year],
                    ['Mileage', fn ($v) => number_format($v->mileage) . ' km'],
                    ['Body type', fn ($v) => ucfirst((string) $v->body_type)],
                    ['Transmission', fn ($v) => ucfirst((string) $v->transmission)],
                    ['Fuel', fn ($v) => ucfirst((string) $v->fuel_type)],
                    ['Condition', fn ($v) => ucfirst((string) $v->condition)],
                    ['Steering', fn ($v) => $v->steering ? strtoupper($v->steering) : '—'],
                    ['Seller', fn ($v) => $v->isListedByVendor() ? 'Dealer' : 'Private'],
                ];
            @endphp

            <div class="overflow-x-auto bg-white border border-neutral-200 rounded-xl shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-100">
                            <th class="px-5 py-4 text-left text-xs font-medium text-neutral-400 uppercase tracking-wide w-40">Listing</th>
                            @foreach ($vehicles as $v)
                                <th class="px-5 py-4 text-left align-top min-w-[200px]">
                                    <a href="{{ route('vehicles.show', $v) }}" class="block">
                                        <div class="aspect-video bg-neutral-100 rounded-lg overflow-hidden mb-2 flex items-center justify-center">
                                            <x-listing-thumbnail :cover="$v->coverImage()" :alt="$v->displayTitle()" type="vehicle" />
                                        </div>
                                        <span class="font-semibold text-neutral-900 hover:text-[#F0A820]">{{ $v->displayTitle() }}</span>
                                    </a>
                                    <form method="POST" action="{{ route('compare.remove', $v) }}" class="mt-2">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-neutral-400 hover:text-red-500">Remove</button>
                                    </form>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($rows as [$label, $resolver])
                            <tr>
                                <td class="px-5 py-3 text-xs font-medium text-neutral-500 uppercase tracking-wide">{{ $label }}</td>
                                @foreach ($vehicles as $v)
                                    <td class="px-5 py-3 text-neutral-900 tabular-nums">{{ $resolver($v) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.app>
