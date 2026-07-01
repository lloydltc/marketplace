<x-layouts.app>
    <x-slot:title>My Trade-Ins</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-h1 text-ink">My trade-ins</h1>
            <x-button :href="route('trade-ins.create')" variant="primary">New trade-in</x-button>
        </div>

        @if ($tradeIns->isEmpty())
            <x-empty title="No trade-ins yet" message="Get a free estimate and let verified dealers bid for your vehicle.">
                <x-button :href="route('trade-ins.create')">Value my vehicle</x-button>
            </x-empty>
        @else
            <div class="bg-surface border border-line rounded-xl shadow-e1 divide-y divide-line">
                @foreach ($tradeIns as $t)
                    <a href="{{ route('trade-ins.show', $t) }}" class="flex items-center justify-between px-5 py-4 hover:bg-surface-2 transition-colors">
                        <div>
                            <p class="text-body-sm font-semibold text-ink">{{ $t->title() }}</p>
                            <p class="text-caption text-muted mt-0.5">{{ $t->estimateRange() ?? 'Awaiting comparables' }} · {{ $t->offers_count }} offer(s) · {{ ucfirst($t->status) }}</p>
                        </div>
                        <span class="text-body-sm text-[rgb(var(--info))]">View →</span>
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $tradeIns->links() }}</div>
        @endif
    </div>
</x-layouts.app>
