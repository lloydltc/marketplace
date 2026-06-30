<x-layouts.app>
    <x-slot:title>History report — {{ $report->vehicle?->displayTitle() ?? $report->vin }}</x-slot:title>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-start justify-between gap-4 mb-6 flex-wrap print:mb-2">
            <div>
                <h1 class="text-h1 text-ink">Vehicle history report</h1>
                <p class="text-body-sm text-muted mt-1">
                    {{ $report->vehicle?->displayTitle() ?? $report->vin }}
                    · purchased {{ $report->purchased_at?->toFormattedDateString() }}
                </p>
            </div>
            <div class="flex items-center gap-3 print:hidden">
                <a href="{{ route('history.index') }}" class="text-body-sm text-muted hover:text-ink">My reports</a>
                <button type="button" onclick="window.print()" class="text-body-sm text-[rgb(var(--info))] hover:underline">Download / print PDF</button>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-[rgb(var(--success)/0.12)] border border-[rgb(var(--success)/0.3)] text-[rgb(var(--success))] text-body-sm rounded-lg px-4 py-3 print:hidden">{{ session('status') }}</div>
        @endif

        <div class="space-y-4">
            @foreach ($report->sections as $section)
                <x-history-section :section="$section" />
            @endforeach
        </div>

        <p class="text-caption text-muted mt-6">
            Compiled {{ $report->updated_at->toFormattedDateString() }}. Each section shows its source, provenance
            and confidence. Sources marked "not available" have no data on file — nothing here is fabricated. This
            report is informational and does not replace an independent mechanical inspection.
        </p>
    </div>
</x-layouts.app>
