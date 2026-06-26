@props([
    'name',                  // unique key; open with $dispatch('open-modal', 'name')
    'title' => null,
    'size'  => 'md',         // sm | md | lg
])

@php $max = ['sm' => 'max-w-sm', 'md' => 'max-w-lg', 'lg' => 'max-w-2xl'][$size] ?? 'max-w-lg'; @endphp

<div x-data="{ open: false }" x-cloak
     x-on:open-modal.window="if ($event.detail === @js($name)) { open = true }"
     x-on:close-modal.window="if ($event.detail === @js($name) || $event.detail === undefined) { open = false }"
     x-on:keydown.escape.window="open = false"
     x-show="open" class="fixed inset-0 z-modal">

    {{-- backdrop --}}
    <div x-show="open" x-transition.opacity.duration.200ms @click="open = false"
         class="absolute inset-0 bg-black/50" aria-hidden="true"></div>

    {{-- panel --}}
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-y-auto">
        <div x-show="open" x-trap.noscroll.inert="open"
             x-transition:enter="transition ease-standard duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             role="dialog" aria-modal="true" @if ($title) aria-label="{{ $title }}" @endif
             {{ $attributes->class("relative w-full $max bg-surface shadow-e4 rounded-t-xl sm:rounded-xl") }}>

            @if ($title || isset($actions))
                <div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-line">
                    <h2 class="text-h3 text-[rgb(var(--text-strong))]">{{ $title }}</h2>
                    <x-icon-button label="Close" @click="open = false">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" /></svg>
                    </x-icon-button>
                </div>
            @endif

            <div class="px-5 py-4 text-[rgb(var(--text))]">{{ $slot }}</div>

            @isset($actions)
                <div class="flex items-center justify-end gap-3 px-5 py-4 border-t border-line">{{ $actions }}</div>
            @endisset
        </div>
    </div>
</div>
