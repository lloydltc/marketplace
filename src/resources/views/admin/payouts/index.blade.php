<x-layouts.app>
    <x-slot:title>Vendor Payouts</x-slot:title>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900">Vendor Payouts</h1>
                <p class="text-sm text-neutral-500 mt-1">Weekly batches to verified bank accounts. Approval posts the wallet debit.</p>
            </div>
            <form method="POST" action="{{ route('admin.payouts.generate') }}">
                @csrf
                <button type="submit" class="bg-[#1A1A24] hover:bg-[#080810] text-white text-sm font-medium px-4 py-2 rounded-lg">
                    Generate weekly batch
                </button>
            </form>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-neutral-50 border-b border-neutral-200 text-neutral-500">
                        <th class="text-left font-medium px-4 py-3">Vendor</th>
                        <th class="text-left font-medium px-4 py-3 hidden sm:table-cell">Period</th>
                        <th class="text-right font-medium px-4 py-3">Amount</th>
                        <th class="text-left font-medium px-4 py-3">Status</th>
                        <th class="text-right font-medium px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse ($payouts as $payout)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3">
                                <div class="text-neutral-800">{{ $payout->vendor?->name }}</div>
                                <div class="text-xs text-neutral-400">{{ $payout->bankAccount?->bank_name ?? 'No verified bank account' }}</div>
                            </td>
                            <td class="px-4 py-3 text-neutral-500 hidden sm:table-cell text-xs">{{ $payout->period_start->format('d M') }} – {{ $payout->period_end->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-neutral-800">{{ $payout->currency }} {{ number_format($payout->amount, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    {{ match($payout->status) {
                                        'pending' => 'bg-neutral-100 text-neutral-600',
                                        'approved' => 'bg-blue-50 text-blue-700',
                                        'paid' => 'bg-green-50 text-green-700',
                                        'rejected' => 'bg-red-50 text-red-600',
                                        default => 'bg-neutral-100 text-neutral-600',
                                    } }}">{{ ucfirst($payout->status) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($payout->status === 'pending')
                                        <form method="POST" action="{{ route('admin.payouts.approve', $payout) }}" onsubmit="return confirm('Approve and debit this payout?')">
                                            @csrf
                                            <button type="submit" class="text-sm text-green-600 hover:underline">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.payouts.reject', $payout) }}">
                                            @csrf
                                            <button type="submit" class="text-sm text-red-500 hover:underline">Reject</button>
                                        </form>
                                    @elseif ($payout->status === 'approved')
                                        <form method="POST" action="{{ route('admin.payouts.mark-paid', $payout) }}" class="flex items-center gap-1">
                                            @csrf
                                            <input type="text" name="reference" required placeholder="Bank ref"
                                                   class="w-28 border border-neutral-300 rounded px-2 py-1 text-xs">
                                            <button type="submit" class="text-sm text-[#3DB8E8] hover:underline">Mark paid</button>
                                        </form>
                                    @elseif ($payout->status === 'paid')
                                        <span class="text-xs text-neutral-400 font-mono">{{ $payout->reference }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-neutral-400">No payouts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($payouts->hasPages())
                <div class="px-4 py-3 border-t border-neutral-100">{{ $payouts->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
