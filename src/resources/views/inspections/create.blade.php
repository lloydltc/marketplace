<x-layouts.app>
    <x-slot:title>Book an inspection</x-slot:title>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-breadcrumbs class="mb-6" :items="[
            ['label' => 'Vehicles', 'url' => route('vehicles.index')],
            ['label' => $vehicle->displayTitle(), 'url' => route('vehicles.show', $vehicle)],
            ['label' => 'Book inspection'],
        ]" />
        <h1 class="text-h1 text-ink mb-1">Book an inspection</h1>
        <p class="text-body-sm text-muted mb-6">{{ $vehicle->displayTitle() }} · fee USD {{ number_format($feeUsd, 2) }}. A vetted inspector checks the vehicle and sends you a standardized report.</p>

        @if ($errors->any())
            <div class="mb-5 bg-[rgb(var(--danger)/0.12)] border border-[rgb(var(--danger)/0.3)] text-[rgb(var(--danger))] text-body-sm rounded-lg px-4 py-3">
                <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @if ($inspectors->isEmpty())
            <x-empty title="No inspectors available yet" message="Our vetted inspector panel is being built — check back soon." />
        @else
            <form method="POST" action="{{ route('inspections.store', $vehicle) }}" class="bg-surface border border-line rounded-xl shadow-e1 p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-body-sm font-medium text-ink mb-2">Choose an inspector</label>
                    <div class="space-y-2">
                        @foreach ($inspectors as $inspector)
                            <label class="flex items-center gap-3 border border-line rounded-lg p-3 cursor-pointer hover:bg-surface-2">
                                <input type="radio" name="inspector_id" value="{{ $inspector->id }}" required class="text-brand">
                                <span class="flex-1">
                                    <span class="font-medium text-ink">{{ $inspector->name }}</span>
                                    <span class="text-caption text-muted"> · {{ ucfirst($inspector->kind) }}@if($inspector->coverage_area) · {{ $inspector->coverage_area }} @endif</span>
                                    @if ($inspector->review_count > 0)<span class="text-caption text-[rgb(var(--warning))]"> · ★ {{ $inspector->rating }} ({{ $inspector->review_count }})</span>@endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-body-sm font-medium text-ink mb-1">Preferred date/time (optional)</label>
                    <input type="datetime-local" name="scheduled_for" class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                </div>
                <div class="flex justify-end pt-2">
                    <x-button type="submit" variant="primary" size="lg">Book &amp; pay USD {{ number_format($feeUsd, 2) }}</x-button>
                </div>
            </form>
        @endif
    </div>
</x-layouts.app>
