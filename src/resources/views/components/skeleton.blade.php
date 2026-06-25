@props([
    'class' => 'h-4 w-full',  // shape via utilities
])

{{-- Shimmer placeholder; static under prefers-reduced-motion (handled globally). --}}
<div aria-hidden="true"
     {{ $attributes->class("animate-pulse motion-reduce:animate-none rounded-md bg-surface-2 $class") }}></div>
