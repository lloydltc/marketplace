<x-layouts.app>
    <x-slot:title>My History Reports</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-h1 text-ink mb-6">My history reports</h1>

        @if ($reports->isEmpty())
            <x-empty title="No reports yet" message="Buy a vehicle history report from any listing to see it here." />
        @else
            <div class="bg-surface border border-line rounded-xl shadow-e1 divide-y divide-line">
                @foreach ($reports as $report)
                    <a href="{{ route('history.show', $report) }}" class="flex items-center justify-between px-5 py-4 hover:bg-surface-2 transition-colors">
                        <div>
                            <p class="text-body-sm font-semibold text-ink">{{ $report->vehicle?->displayTitle() ?? $report->vin ?? 'Vehicle report' }}</p>
                            <p class="text-caption text-muted mt-0.5">Purchased {{ $report->purchased_at?->toFormattedDateString() }}</p>
                        </div>
                        <span class="text-body-sm text-[rgb(var(--info))]">View →</span>
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $reports->links() }}</div>
        @endif
    </div>
</x-layouts.app>
