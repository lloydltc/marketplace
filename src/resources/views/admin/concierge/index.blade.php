<x-layouts.app>
    <x-slot:title>Concierge Queue</x-slot:title>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-2xl font-semibold text-neutral-900 mb-1">Concierge Queue</h1>
        <p class="text-sm text-neutral-500 mb-6">Source, quote, collect payment, fulfil and close — end to end.</p>

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-neutral-50 border-b border-neutral-200 text-neutral-500">
                        <th class="text-left font-medium px-4 py-3">Request</th>
                        <th class="text-left font-medium px-4 py-3 hidden sm:table-cell">Buyer</th>
                        <th class="text-left font-medium px-4 py-3">Status</th>
                        <th class="text-right font-medium px-4 py-3">Total</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse ($requests as $req)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 text-neutral-800">{{ Str::limit($req->part_description, 50) }}</td>
                            <td class="px-4 py-3 text-neutral-500 hidden sm:table-cell">{{ $req->buyer?->name }}</td>
                            <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600">{{ ucfirst($req->status) }}</span></td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $req->total ? 'ZWL ' . number_format($req->total, 2) : '—' }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('admin.concierge.show', $req) }}" class="text-sm text-[#3DB8E8] hover:underline">Manage</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-neutral-400">No active concierge requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($requests->hasPages())
                <div class="px-4 py-3 border-t border-neutral-100">{{ $requests->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
