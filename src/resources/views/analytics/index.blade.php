<x-layouts.app>
    <x-slot:title>Listing analytics</x-slot:title>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-h1 text-ink mb-1">Listing analytics</h1>
        <p class="text-body-sm text-muted mb-6">Last 30 days ({{ $start->format('d M') }} – {{ $end->format('d M') }}) vs the previous 30. Bot traffic and repeat views are filtered.</p>

        @php
            $labels = [
                'detail_view'    => 'Views',
                'phone_reveal'   => 'Phone reveals',
                'whatsapp_click' => 'WhatsApp clicks',
                'call_click'     => 'Calls',
                'enquiry'        => 'Enquiries',
            ];
            $max = max(array_map(fn ($m) => $m['count'], $metrics)) ?: 1;
        @endphp

        {{-- Instrument-cluster stat tiles (signature) --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-5 mb-8">
            @foreach ($labels as $type => $label)
                @php $m = $metrics[$type]; @endphp
                <x-stat-tile
                    :label="$label"
                    :value="number_format($m['count'])"
                    :delta="$m['delta'] !== 0 ? $m['delta'] : null"
                    delta-suffix=""
                    :caption="$m['delta'] === 0 ? 'no change' : null"
                    :arc="$m['count'] / $max" />
            @endforeach
        </div>

        <h2 class="text-h4 text-ink mb-3">Per-listing (last 30 days)</h2>
        @if ($perListing->isEmpty())
            <x-empty title="No views yet" message="Once buyers browse your listings, the numbers appear here." />
        @else
            <x-table>
                <x-slot:head>
                    <th>Listing</th>
                    <th class="!text-right">Views</th>
                    <th class="!text-right">Contacts</th>
                </x-slot:head>
                @foreach ($perListing as $row)
                    <tr>
                        <td>
                            <a href="{{ route('vehicles.show', $row['vehicle']) }}" class="font-medium text-ink hover:text-brand transition-colors">{{ $row['vehicle']->displayTitle() }}</a>
                        </td>
                        <td class="text-right tabular-nums">{{ number_format($row['views']) }}</td>
                        <td class="text-right tabular-nums font-medium text-ink">{{ number_format($row['contacts']) }}</td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </div>
</x-layouts.app>
