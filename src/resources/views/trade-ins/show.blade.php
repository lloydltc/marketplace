<x-layouts.app>
    <x-slot:title>Trade-in — {{ $tradeIn->title() }}</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <a href="{{ route('trade-ins.index') }}" class="text-body-sm text-muted hover:text-ink">← My trade-ins</a>
        <h1 class="text-h1 text-ink mt-2 mb-1">{{ $tradeIn->title() }}</h1>
        <p class="text-body-sm text-muted mb-6">{{ number_format($tradeIn->mileage) }} km · {{ ucfirst($tradeIn->condition) }} · Status: {{ ucfirst($tradeIn->status) }}</p>

        @if (session('status'))
            <div class="mb-5 bg-[rgb(var(--success)/0.12)] border border-[rgb(var(--success)/0.3)] text-[rgb(var(--success))] text-body-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        {{-- Estimate --}}
        <x-card padding="lg" class="mb-6">
            <h2 class="text-h4 text-ink mb-2">Estimated trade-in value</h2>
            @if ($tradeIn->hasEstimate())
                <p class="text-h2 text-ink tabular-nums">{{ $tradeIn->estimateRange() }}</p>
                <p class="text-caption text-muted mt-2">Based on {{ $tradeIn->comparables_count }} comparable listing(s), adjusted for mileage & condition. This is an <strong>estimate, not an offer</strong> — dealer bids below are firm.</p>
            @else
                <p class="text-body-sm text-muted">Not enough comparable listings yet to estimate a range. Verified dealers can still bid below.</p>
            @endif
        </x-card>

        {{-- Dealer offers (TI2) --}}
        <x-card padding="lg">
            <h2 class="text-h4 text-ink mb-3">Dealer offers ({{ $tradeIn->offers->count() }})</h2>
            @if ($tradeIn->offers->isEmpty())
                <p class="text-body-sm text-muted">No dealer offers yet. Verified dealers are notified of new submissions.</p>
            @else
                <div class="space-y-3">
                    @foreach ($tradeIn->offers as $offer)
                        <div class="flex items-center justify-between gap-3 border border-line rounded-lg p-3 {{ $offer->status === 'accepted' ? 'ring-1 ring-[rgb(var(--success)/0.5)]' : '' }}">
                            <div>
                                <div class="text-body font-bold text-ink tabular-nums">{{ $offer->amount() }}</div>
                                <div class="text-caption text-muted">{{ $offer->vendor?->name }} @if($offer->notes) · {{ $offer->notes }} @endif</div>
                            </div>
                            @if ($offer->status === 'accepted')
                                <span class="text-caption font-semibold text-[rgb(var(--success))]">Accepted</span>
                            @elseif ($tradeIn->status !== 'accepted')
                                <form method="POST" action="{{ route('trade-ins.offers.accept', [$tradeIn, $offer]) }}">
                                    @csrf
                                    <x-button type="submit" variant="primary" size="sm">Accept</x-button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.app>
