<x-layouts.app>
    <x-slot:title>Rider Cash Sessions</x-slot:title>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-2xl font-semibold text-neutral-900 mb-1">Rider Cash Reconciliation</h1>
        <p class="text-sm text-neutral-500 mb-6">FBS cash-on-delivery orders settle only after the rider's cash is reconciled here.</p>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-neutral-50 border-b border-neutral-200 text-neutral-500">
                        <th class="text-left font-medium px-4 py-3">Rider</th>
                        <th class="text-left font-medium px-4 py-3">Date</th>
                        <th class="text-right font-medium px-4 py-3">Expected</th>
                        <th class="text-right font-medium px-4 py-3">Collected</th>
                        <th class="text-left font-medium px-4 py-3">Status</th>
                        <th class="text-right font-medium px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse ($sessions as $session)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 text-neutral-800">{{ $session->rider?->name }}</td>
                            <td class="px-4 py-3 text-neutral-500">{{ $session->session_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($session->expected_total, 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $session->collected_total !== null ? number_format($session->collected_total, 2) : '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-0.5 rounded-full {{ match($session->status) {
                                    'open' => 'bg-neutral-100 text-neutral-600',
                                    'reconciled' => 'bg-green-50 text-green-700',
                                    'discrepancy' => 'bg-red-50 text-red-600',
                                    default => 'bg-neutral-100',
                                } }}">{{ ucfirst($session->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($session->status === 'open')
                                    <form method="POST" action="{{ route('admin.cash-sessions.reconcile', $session) }}" class="flex items-center justify-end gap-1">
                                        @csrf
                                        <input type="number" name="collected_total" step="0.01" min="0" required placeholder="Cash in"
                                               class="w-24 border border-neutral-300 rounded px-2 py-1 text-xs">
                                        <button type="submit" class="text-sm text-green-600 hover:underline">Reconcile</button>
                                    </form>
                                @elseif ($session->status === 'discrepancy')
                                    <form method="POST" action="{{ route('admin.cash-sessions.resolve', $session) }}" onsubmit="return confirm('Accept the discrepancy and settle these orders?')">
                                        @csrf
                                        <button type="submit" class="text-sm text-red-500 hover:underline">Resolve &amp; settle</button>
                                    </form>
                                @else
                                    <span class="text-xs text-neutral-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-neutral-400">No cash sessions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($sessions->hasPages())
                <div class="px-4 py-3 border-t border-neutral-100">{{ $sessions->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
