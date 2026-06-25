@props([
    'name',                  // open with $dispatch('open-drawer', 'name')
    'side'  => 'right',      // right (filters/cart) | bottom (mobile actions)
    'title' => null,
])

@php
    $panel = $side === 'bottom'
        ? 'inset-x-0 bottom-0 w-full max-h-[85vh] rounded-t-2xl'
        : 'inset-y-0 right-0 h-full w-full max-w-sm';
    $enterStart = $side === 'bottom' ? 'translate-y-full' : 'translate-x-full';
@endphp

<div x-data="{ open: false }" x-cloak
     x-on:open-drawer.window="if ($event.detail === @js($name)) { open = true }"
     x-on:close-drawer.window="if ($event.detail === @js($name) || $event.detail === undefined) { open = false }"
     x-on:keydown.escape.window="open = false"
     x-show="open" class="fixed inset-0 z-drawer">

    <div x-show="open" x-transition.opacity.duration.200ms @click="open = false"
         class="absolute inset-0 bg-black/50" aria-hidden="true"></div>

    <div x-show="open" x-trap.noscroll.inert="open"
         x-transition:enter="transition transform ease-standard duration-300"
         x-transition:enter-start="{{ $enterStart }}" x-transition:enter-end="translate-x-0 translate-y-0"
         x-transition:leave="transition transform ease-standard duration-200"
         x-transition:leave-start="translate-x-0 translate-y-0" x-transition:leave-end="{{ $enterStart }}"
         role="dialog" aria-modal="true" @if ($title) aria-label="{{ $title }}" @endif
         {{ $attributes->class("absolute $panel bg-surface shadow-e4 flex flex-col") }}>

        <div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-base shrink-0">
            <h2 class="text-h3 text-[rgb(var(--text-strong))]">{{ $title }}</h2>
            <x-icon-button label="Close" @click="open = false">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" /></svg>
            </x-icon-button>
        </div>

        <div class="flex-1 overflow-y-auto px-5 py-4 text-[rgb(var(--text))]">{{ $slot }}</div>

        @isset($footer)
            <div class="shrink-0 px-5 py-4 border-t border-base">{{ $footer }}</div>
        @endisset
    </div>
</div>
