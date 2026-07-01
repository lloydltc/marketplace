<x-layouts.app>
    <x-slot:title>Trade-ins</x-slot:title>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="lg:flex lg:gap-8">
        <x-admin-sidebar />
        <div class="flex-1 min-w-0 mt-6 lg:mt-0">
            <h1 class="text-h1 text-ink mb-6">Trade-ins</h1>
            <div class="bg-surface border border-line rounded-xl shadow-e1 overflow-hidden">
                @if ($tradeIns->isEmpty())
                    <div class="px-5 py-12 text-center text-body-sm text-muted">No trade-ins yet.</div>
                @else
                    <table class="w-full text-body-sm">
                        <thead><tr class="text-left text-overline uppercase text-muted border-b border-line">
                            <th class="px-5 py-2 font-medium">Vehicle</th><th class="px-5 py-2 font-medium">Seller</th>
                            <th class="px-5 py-2 font-medium">Estimate</th><th class="px-5 py-2 font-medium text-right">Offers</th>
                            <th class="px-5 py-2 font-medium text-right">Status</th>
                        </tr></thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($tradeIns as $t)
                                <tr class="hover:bg-surface-2">
                                    <td class="px-5 py-3"><a href="{{ route('admin.trade-ins.show', $t) }}" class="font-medium text-ink hover:text-brand">{{ $t->title() }}</a></td>
                                    <td class="px-5 py-3 text-muted">{{ $t->user?->name }}</td>
                                    <td class="px-5 py-3 tabular-nums">{{ $t->estimateRange() ?? '—' }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums">{{ $t->offers_count }}</td>
                                    <td class="px-5 py-3 text-right"><span class="capitalize text-muted">{{ $t->status }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
            <div class="mt-6">{{ $tradeIns->links() }}</div>
        </div>
      </div>
    </div>
</x-layouts.app>
