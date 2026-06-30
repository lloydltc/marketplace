<x-layouts.app>
    <x-slot:title>History report — {{ $vehicle->displayTitle() }}</x-slot:title>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-breadcrumbs class="mb-6" :items="[
            ['label' => 'Vehicles', 'url' => route('vehicles.index')],
            ['label' => $vehicle->displayTitle(), 'url' => route('vehicles.show', $vehicle)],
            ['label' => 'History report'],
        ]" />

        <div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
            <div>
                <h1 class="text-h1 text-ink">Vehicle history report</h1>
                <p class="text-body-sm text-muted mt-1">{{ $vehicle->displayTitle() }} — free preview below.</p>
            </div>
            @unless ($report->isPurchased())
                <form method="POST" action="{{ route('history.purchase', $vehicle) }}">
                    @csrf
                    <x-button type="submit" variant="primary" size="lg">
                        @if ($report->price_minor > 0)
                            Buy full report — USD {{ number_format($report->priceUsd(), 2) }}
                        @else
                            Get full report (free)
                        @endif
                    </x-button>
                </form>
            @else
                <x-button :href="route('history.show', $report)" variant="primary" size="lg">View full report</x-button>
            @endunless
        </div>

        <div class="space-y-4">
            @foreach ($report->sections as $section)
                <x-history-section :section="$section" :locked="! in_array($section->type, $previewTypes, true) && ! $report->isPurchased()" />
            @endforeach
        </div>

        <p class="text-caption text-muted mt-6">
            Reports are compiled from the sources shown, each with its provenance and confidence. Some data
            sources are not yet available in Zimbabwe and are marked accordingly — SalmaDrive never fabricates
            history data. A report is informational and does not replace independent inspection.
        </p>
    </div>
</x-layouts.app>
