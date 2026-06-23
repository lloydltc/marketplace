<x-layouts.app>
    <x-slot:title>Listing Analytics</x-slot:title>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-2xl font-semibold text-neutral-900 mb-1">Listing Analytics</h1>
        <p class="text-sm text-neutral-500 mb-6">Last 30 days ({{ $start->format('d M') }} – {{ $end->format('d M') }}) vs the previous 30. Bot traffic and repeat views are filtered.</p>

        @php
            $labels = [
                'detail_view'    => 'Views',
                'phone_reveal'   => 'Phone reveals',
                'whatsapp_click' => 'WhatsApp clicks',
                'call_click'     => 'Calls',
                'enquiry'        => 'Enquiries',
            ];
        @endphp

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            @foreach ($labels as $type => $label)
                @php $m = $metrics[$type]; @endphp
                <div class="bg-white border border-neutral-200 rounded-xl p-5 shadow-sm">
                    <p class="text-sm font-medium text-neutral-500">{{ $label }}</p>
                    <p class="mt-2 text-3xl font-semibold text-neutral-900 tabular-nums">{{ number_format($m['count']) }}</p>
                    @if ($m['delta'] > 0)
                        <p class="mt-1 text-xs font-medium text-[#2EBD7A]">▲ {{ number_format($m['delta']) }}</p>
                    @elseif ($m['delta'] < 0)
                        <p class="mt-1 text-xs font-medium text-red-500">▼ {{ number_format(abs($m['delta'])) }}</p>
                    @else
                        <p class="mt-1 text-xs text-neutral-400">no change</p>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm">
            <div class="px-5 py-4 border-b border-neutral-100">
                <h2 class="text-base font-semibold text-neutral-900">Per-listing (last 30 days)</h2>
            </div>
            @if ($perListing->isEmpty())
                <div class="px-5 py-12 text-center text-sm text-neutral-500">No views yet. Once buyers browse your listings, the numbers appear here.</div>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-neutral-400 uppercase tracking-wide">
                            <th class="px-5 py-2 font-medium">Listing</th>
                            <th class="px-5 py-2 font-medium text-right">Views</th>
                            <th class="px-5 py-2 font-medium text-right">Contacts</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($perListing as $row)
                            <tr class="hover:bg-neutral-50">
                                <td class="px-5 py-3">
                                    <a href="{{ route('vehicles.show', $row['vehicle']) }}" class="font-medium text-neutral-900 hover:text-[#F0A820]">{{ $row['vehicle']->displayTitle() }}</a>
                                </td>
                                <td class="px-5 py-3 text-right tabular-nums">{{ number_format($row['views']) }}</td>
                                <td class="px-5 py-3 text-right tabular-nums font-medium">{{ number_format($row['contacts']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-layouts.app>
