@props([
    'label',                  // required — accessible name
    'variant' => 'ghost',     // ghost | outline | secondary
    'size'    => 'md',         // sm | md | lg
    'href'    => null,
    'type'    => 'button',
])

@php
    $base = 'inline-flex items-center justify-center rounded-md transition-colors duration-150 ease-standard '
          . 'focus-visible:outline-none disabled:opacity-50 disabled:pointer-events-none';

    $variants = [
        'ghost'     => 'text-[rgb(var(--text-muted))] hover:text-[rgb(var(--text))] hover:bg-surface-2',
        'outline'   => 'border border-strong text-[rgb(var(--text))] hover:bg-surface-2',
        'secondary' => 'bg-surface-2 text-[rgb(var(--text))] hover:bg-[rgb(var(--border))]',
    ];

    // square; md = 44px touch target
    $sizes = ['sm' => 'size-9', 'md' => 'size-11', 'lg' => 'size-12'];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['ghost']) . ' ' . ($sizes[$size] ?? $sizes['md']);
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    aria-label="{{ $label }}" title="{{ $label }}"
    {{ $attributes->class($classes) }}
>
    {{ $slot }}
</{{ $tag }}>
