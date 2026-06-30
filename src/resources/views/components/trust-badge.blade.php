@props([
    'vendor',
    'size' => 'sm',   // sm | xs
])

@php
    $tier = $vendor?->verification_tier;
    $cfg = $tier ? config("verification.tiers.{$tier}") : null;
@endphp

@if ($cfg)
    {{-- Trust tier badge — icon + label (never colour-only). --}}
    <span {{ $attributes->class([
            'inline-flex items-center gap-1 rounded-full font-semibold bg-[rgb(var(--brand)/0.15)] text-brand',
            'px-2 py-0.5 text-caption' => $size === 'sm',
            'px-1.5 py-0.5 text-[10px]' => $size === 'xs',
        ]) }}
        title="{{ $cfg['label'] }}">
        <span aria-hidden="true">{{ $cfg['icon'] }}</span>
        <span>{{ $cfg['label'] }}</span>
    </span>
@endif
