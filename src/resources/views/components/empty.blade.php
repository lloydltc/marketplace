@props([
    'title',                 // one-line cause, e.g. "No listings yet"
    'message' => null,       // optional supporting line
    'icon'    => true,       // show default glyph; pass a slot:icon to override
])

<div {{ $attributes->class('flex flex-col items-center justify-center text-center px-6 py-14 rounded-lg border border-dashed border-strong bg-surface') }}>
    @if ($icon)
        <div class="mb-4 grid place-items-center size-12 rounded-full bg-surface-2 text-[rgb(var(--text-muted))]">
            @isset($iconSlot){{ $iconSlot }}@else
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                </svg>
            @endisset
        </div>
    @endif
    <h3 class="text-h4 text-[rgb(var(--text-strong))]">{{ $title }}</h3>
    @if ($message)<p class="mt-1.5 max-w-sm text-body-sm text-[rgb(var(--text-muted))]">{{ $message }}</p>@endif
    @if (! $slot->isEmpty())<div class="mt-5">{{ $slot }}</div>@endif
</div>
