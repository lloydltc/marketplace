@props([
    'text',                  // tooltip content — never the only source of essential info
    'placement' => 'top',    // top | bottom
])

@php $id = 'tt-' . Str::random(6); @endphp

<span x-data="{ open: false }" class="relative inline-flex">
    <span @mouseenter="open = true" @mouseleave="open = false"
          @focusin="open = true" @focusout="open = false"
          aria-describedby="{{ $id }}" {{ $attributes }}>
        {{ $slot }}
    </span>
    <span id="{{ $id }}" role="tooltip" x-show="open" x-cloak x-transition.opacity.duration.200ms
          @class([
              'absolute left-1/2 -translate-x-1/2 z-tooltip w-max max-w-xs px-2.5 py-1.5 rounded-md',
              'bg-[rgb(var(--bg-sidebar))] text-white text-caption shadow-e3 pointer-events-none',
              'bottom-full mb-2' => $placement === 'top',
              'top-full mt-2'    => $placement === 'bottom',
          ])>
        {{ $text }}
    </span>
</span>
