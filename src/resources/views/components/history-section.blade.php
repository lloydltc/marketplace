@props([
    'section',
    'locked' => false,
])

@php
    $labels = [
        'import' => 'Import record', 'ownership' => 'Ownership & listing history',
        'odometer' => 'Odometer', 'service' => 'Service history',
        'registration' => 'Registration / ZINARA', 'police_clearance' => 'Police clearance',
        'roadworthiness' => 'Roadworthiness', 'insurance' => 'Insurance / accident',
    ];
    $confColour = ['high' => 'success', 'medium' => 'info', 'low' => 'warning'][$section->confidence] ?? 'info';
@endphp

<x-card padding="lg" class="break-inside-avoid">
    <div class="flex items-start justify-between gap-3 mb-3">
        <h3 class="text-h4 text-ink">{{ $labels[$section->type] ?? Str::headline($section->type) }}</h3>
        @if ($section->isAvailable())
            <span class="text-caption font-medium px-2 py-0.5 rounded-full bg-[rgb(var(--{{ $confColour }})/0.15)] text-[rgb(var(--{{ $confColour }}))]">
                {{ ucfirst($section->confidence) }} confidence
            </span>
        @else
            <span class="text-caption font-medium px-2 py-0.5 rounded-full bg-surface-2 text-muted">Not available</span>
        @endif
    </div>

    @if ($locked)
        <div class="flex items-center gap-2 text-body-sm text-muted">
            <span aria-hidden="true">🔒</span> Purchase the full report to view this section.
        </div>
    @elseif (! $section->isAvailable())
        <p class="text-body-sm text-muted">{{ $section->provenance ?: 'This data source is not available yet.' }}</p>
    @elseif (empty($section->data))
        <p class="text-body-sm text-muted">No records on file.</p>
    @else
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-body-sm">
            @foreach ($section->data as $key => $value)
                @continue(is_array($value) && $value === [])
                <div class="flex justify-between gap-3 border-b border-line py-1.5 {{ is_array($value) ? 'sm:col-span-2' : '' }}">
                    <dt class="text-muted">{{ Str::headline((string) $key) }}</dt>
                    <dd class="font-medium text-ink text-right">
                        @if (is_array($value))
                            <ul class="space-y-0.5">
                                @foreach ($value as $row)
                                    <li>{{ is_array($row) ? collect($row)->map(fn ($v, $k) => is_bool($v) ? ($v ? $k : '') : $v)->filter()->implode(' · ') : $row }}</li>
                                @endforeach
                            </ul>
                        @elseif (is_bool($value))
                            {{ $value ? 'Yes' : 'No' }}
                        @else
                            {{ $value }}
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    @endif

    @if ($section->isAvailable() && ! $locked && $section->provenance)
        <p class="text-caption text-muted mt-3">
            Source: {{ $section->provenance }}@if ($section->retrieved_at) · retrieved {{ $section->retrieved_at->toFormattedDateString() }}@endif
        </p>
    @endif
</x-card>
