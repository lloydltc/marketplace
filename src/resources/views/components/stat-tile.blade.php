@props([
    'label',                 // overline caption, e.g. "Profile views"
    'value',                 // the big figure (already formatted)
    'delta'    => null,      // signed number, e.g. 12 or -4 (percent); null hides
    'deltaSuffix' => '%',
    'caption'  => null,      // optional sub-line under the figure
    'arc'      => 0.0,       // 0..1 — fills the gold gauge arc
])

@php
    $up = $delta !== null && $delta >= 0;
    $deltaCls = $up ? 'text-[rgb(var(--success))]' : 'text-[rgb(var(--danger))]';
    // gauge arc geometry — 252° sweep, like an instrument cluster
    $r = 26; $cx = 30; $cy = 30;
    $sweep = 252; $start = 144; // degrees
    $circ = 2 * M_PI * $r;
    $arcLen = $circ * ($sweep / 360);
    $fill = max(0.0, min(1.0, (float) $arc));
@endphp

<x-card padding="md" {{ $attributes }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-overline uppercase text-[rgb(var(--text-muted))]">{{ $label }}</p>
            <p class="mt-1 text-display-lg tabular-nums text-[rgb(var(--text-strong))] leading-none truncate">{{ $value }}</p>
            @if ($delta !== null)
                <p class="mt-2 inline-flex items-center gap-1 text-caption font-semibold {{ $deltaCls }}">
                    <span aria-hidden="true">{{ $up ? '▲' : '▼' }}</span>
                    <span class="tabular-nums">{{ abs($delta) }}{{ $deltaSuffix }}</span>
                </p>
            @endif
            @if ($caption)<p class="mt-1 text-caption text-[rgb(var(--text-muted))]">{{ $caption }}</p>@endif
        </div>

        {{-- gold instrument arc --}}
        <svg viewBox="0 0 60 60" class="size-14 shrink-0 -rotate-90 origin-center" aria-hidden="true">
            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none"
                    stroke="rgb(var(--border))" stroke-width="5" stroke-linecap="round"
                    stroke-dasharray="{{ $arcLen }} {{ $circ }}"
                    transform="rotate({{ $start }} {{ $cx }} {{ $cy }})" />
            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none"
                    stroke="rgb(var(--brand))" stroke-width="5" stroke-linecap="round"
                    stroke-dasharray="{{ $arcLen * $fill }} {{ $circ }}"
                    transform="rotate({{ $start }} {{ $cx }} {{ $cy }})" />
        </svg>
    </div>
</x-card>
