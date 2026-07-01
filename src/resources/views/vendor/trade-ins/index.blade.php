<x-layouts.app>
    <x-slot:title>Trade-in bids</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="lg:flex lg:gap-8">
        <x-vendor-sidebar />
        <div class="flex-1 min-w-0 mt-6 lg:mt-0">

        @if (session('status'))
            <div class="mb-5 bg-[rgb(var(--success)/0.12)] border border-[rgb(var(--success)/0.3)] text-[rgb(var(--success))] text-body-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        <div class="mb-6">
            <h1 class="text-h1 text-ink">Trade-in bids</h1>
            <p class="text-body-sm text-muted mt-1">Open seller trade-ins. Place a firm offer — the seller compares and accepts.</p>
        </div>

        @if ($tradeIns->isEmpty())
            <x-empty title="No open trade-ins" message="New trade-in submissions appear here for you to bid on." />
        @else
            <div class="space-y-4">
                @foreach ($tradeIns as $t)
                    @php $mine = $t->offers->first(); @endphp
                    <x-card padding="lg">
                        <div class="flex items-start justify-between gap-4 flex-wrap">
                            <div>
                                <h2 class="text-h4 text-ink">{{ $t->title() }}</h2>
                                <p class="text-body-sm text-muted mt-0.5">{{ number_format($t->mileage) }} km · {{ ucfirst($t->condition) }} @if($t->hasEstimate()) · est. {{ $t->estimateRange() }} @endif</p>
                                @if ($t->notes)<p class="text-body-sm text-[rgb(var(--text))] mt-1">{{ $t->notes }}</p>@endif
                            </div>
                            @if ($mine)
                                <span class="text-caption font-semibold px-2 py-0.5 rounded-full bg-[rgb(var(--info)/0.15)] text-[rgb(var(--info))]">Your bid: {{ $mine->amount() }}</span>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('vendor.trade-ins.bid', $t) }}" class="flex flex-wrap items-end gap-2 mt-4 pt-4 border-t border-line">
                            @csrf
                            <div>
                                <label class="block text-caption text-muted mb-1">Offer (USD)</label>
                                <input type="number" name="amount" min="1" step="0.01" value="{{ $mine ? $mine->amount_minor / 100 : '' }}" required class="w-36 border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                            </div>
                            <input type="text" name="notes" placeholder="Notes (optional)" maxlength="500" value="{{ $mine?->notes }}" class="flex-1 min-w-[12rem] border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                            <x-button type="submit" variant="primary" size="md">{{ $mine ? 'Update bid' : 'Place bid' }}</x-button>
                        </form>
                    </x-card>
                @endforeach
            </div>
            <div class="mt-6">{{ $tradeIns->links() }}</div>
        @endif
        </div>
      </div>
    </div>
</x-layouts.app>
