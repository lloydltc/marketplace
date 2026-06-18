<x-layouts.app>
    <x-slot:title>Wallet</x-slot:title>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-2xl font-semibold text-neutral-900 mb-6">Wallet</h1>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif
        @error('amount')
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $message }}</div>
        @enderror

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 sm:col-span-2">
                <div class="text-sm text-neutral-500">Available balance</div>
                <div class="text-3xl font-bold text-neutral-900 tabular-nums mt-1">
                    {{ $wallet->currency }} {{ number_format($wallet->cached_balance, 2) }}
                </div>
                @if ($wallet->cached_balance < 0)
                    <p class="text-xs text-red-600 mt-2">Your balance is below zero — new listings and cash-on-delivery are paused until you top up.</p>
                @endif
                <div class="text-xs text-neutral-400 mt-2">
                    @if ($nextPayout)
                        Next payout: {{ $wallet->currency }} {{ number_format($nextPayout->amount, 2) }} ({{ $nextPayout->status }})
                    @else
                        No payout scheduled yet.
                    @endif
                </div>
            </div>

            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5">
                <div class="text-sm font-medium text-neutral-700 mb-2">Top up</div>
                <form method="POST" action="{{ route('vendor.wallet.topup') }}" class="space-y-2">
                    @csrf
                    <input type="number" name="amount" step="0.01" min="1" placeholder="Amount" required
                           class="block w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                    <button type="submit" class="w-full bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold py-2 rounded-lg text-sm">Top up via Pesepay</button>
                </form>
            </div>
        </div>

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-neutral-100 bg-neutral-50 text-sm font-semibold text-neutral-700">Ledger</div>
            @if ($entries->isEmpty())
                <div class="py-12 text-center text-sm text-neutral-400">No transactions yet.</div>
            @else
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($entries as $entry)
                            <tr>
                                <td class="px-5 py-3">
                                    <div class="text-neutral-800">{{ $entry->description ?? str_replace('_', ' ', $entry->type) }}</div>
                                    <div class="text-xs text-neutral-400">{{ $entry->created_at->format('d M Y H:i') }} · {{ str_replace('_', ' ', $entry->type) }}</div>
                                </td>
                                <td class="px-5 py-3 text-right tabular-nums font-medium {{ $entry->isCredit() ? 'text-green-600' : 'text-red-500' }}">
                                    {{ $entry->isCredit() ? '+' : '−' }}{{ number_format($entry->amount, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            @if ($entries->hasPages())
                <div class="px-5 py-3 border-t border-neutral-100">{{ $entries->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
