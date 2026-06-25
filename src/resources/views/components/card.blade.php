@props([
    'padding'     => 'md',     // none | sm | md | lg
    'elevation'   => 'e1',     // e0 | e1 | e2
    'interactive' => false,    // hover lift (≤2px) — for clickable cards
    'as'          => 'div',
])

@php
    $pad = ['none' => '', 'sm' => 'p-4', 'md' => 'p-5', 'lg' => 'p-6'][$padding] ?? 'p-5';
    $elev = ['e0' => '', 'e1' => 'shadow-e1', 'e2' => 'shadow-e2'][$elevation] ?? 'shadow-e1';
    $hover = $interactive
        ? 'transition duration-200 ease-standard hover:shadow-e2 hover:-translate-y-0.5 motion-reduce:hover:translate-y-0'
        : '';
    $classes = "bg-surface border border-base rounded-lg $pad $elev $hover";
@endphp

<{{ $as }} {{ $attributes->class($classes) }}>
    {{ $slot }}
</{{ $as }}>
