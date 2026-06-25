@props([
    'variant' => 'primary',   // primary | secondary | outline | ghost | danger | whatsapp
    'size'    => 'md',         // sm | md | lg
    'href'    => null,         // renders an <a> when present
    'type'    => 'button',
    'loading' => false,
    'disabled' => false,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-semibold rounded-md select-none '
          . 'transition-colors duration-150 ease-standard active:translate-y-px '
          . 'focus-visible:outline-none disabled:opacity-50 disabled:pointer-events-none aria-disabled:opacity-50';

    $variants = [
        'primary'   => 'bg-brand text-on-brand hover:bg-brand-hover',
        'secondary' => 'bg-surface-2 text-[rgb(var(--text-strong))] hover:bg-[rgb(var(--border))]',
        'outline'   => 'border border-strong text-[rgb(var(--text))] hover:bg-surface-2',
        'ghost'     => 'text-[rgb(var(--text))] hover:bg-surface-2',
        'danger'    => 'bg-[rgb(var(--danger))] text-white hover:opacity-90',
        'whatsapp'  => 'bg-[rgb(var(--success))] text-white hover:opacity-90',
    ];

    // h-11 = 44px touch target (md is the mobile default)
    $sizes = [
        'sm' => 'h-9 px-3.5 text-body-sm',
        'md' => 'h-11 px-5 text-body-sm',
        'lg' => 'h-12 px-6 text-body',
    ];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
    $isDisabled = $disabled || $loading;
@endphp

@php $tag = $href ? 'a' : 'button'; @endphp
<{{ $tag }}
    @if ($href) href="{{ $isDisabled ? null : $href }}" @if ($isDisabled) aria-disabled="true" role="button" @endif
    @else type="{{ $type }}" @if ($isDisabled) disabled @endif
    @endif
    @if ($loading) aria-busy="true" @endif
    {{ $attributes->class($classes) }}
>
    @if ($loading)
        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z" />
        </svg>
    @elseif ($variant === 'whatsapp')
        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.82 11.82 0 018.413 3.488 11.82 11.82 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 001.51 5.26l-.999 3.648 3.978-.99z" />
        </svg>
    @endif
    {{ $slot }}
</{{ $tag }}>
