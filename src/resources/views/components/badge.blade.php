@props([
    'variant' => 'neutral',  // featured|verified|unverified|new|used|sold|recent-import|poa|duty-paid|neutral|success|info|danger|warning
    'label'   => null,       // overrides the preset label; slot also works
    'icon'    => true,       // show the preset status glyph (status is never colour-only)
])

@php
    // Each preset: [classes, glyph, default label]. Tints use alpha of the
    // semantic var so they hold up in light AND dark; gold/sold are solid fills.
    $presets = [
        'featured'      => ['bg-brand text-on-brand',                                              '★',  'Featured'],
        'verified'      => ['bg-[rgb(var(--success)/0.16)] text-[rgb(var(--success))]',            '✓',  'Verified'],
        'duty-paid'     => ['bg-[rgb(var(--success)/0.16)] text-[rgb(var(--success))]',            '✓',  'Duty paid'],
        'success'       => ['bg-[rgb(var(--success)/0.16)] text-[rgb(var(--success))]',            '✓',  'Success'],
        'new'           => ['bg-[rgb(var(--info)/0.16)] text-[rgb(var(--info))]',                  '•',  'New'],
        'recent-import' => ['bg-[rgb(var(--info)/0.16)] text-[rgb(var(--info))]',                  '⇪',  'Recent import'],
        'info'          => ['bg-[rgb(var(--info)/0.16)] text-[rgb(var(--info))]',                  'ℹ',  'Info'],
        'used'          => ['bg-surface-2 text-[rgb(var(--text-muted))]',                          null, 'Used'],
        'neutral'       => ['bg-surface-2 text-[rgb(var(--text-muted))]',                          null, ''],
        'sold'          => ['bg-[rgb(var(--danger))] text-white',                                  '●',  'Sold'],
        'danger'        => ['bg-[rgb(var(--danger)/0.16)] text-[rgb(var(--danger))]',              '!',  'Error'],
        'unverified'    => ['border border-[rgb(var(--warning)/0.5)] text-[rgb(var(--warning))]',  '!',  'Unverified'],
        'warning'       => ['border border-[rgb(var(--warning)/0.5)] text-[rgb(var(--warning))]',  '!',  'Warning'],
        'poa'           => ['border border-strong text-[rgb(var(--text-muted))]',                  null, 'POA'],
    ];

    [$classes, $glyph, $default] = $presets[$variant] ?? $presets['neutral'];
    $text = $label ?? ($slot->isEmpty() ? $default : null);
@endphp

<span {{ $attributes->class("inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-caption font-semibold whitespace-nowrap $classes") }}>
    @if ($icon && $glyph)<span aria-hidden="true">{{ $glyph }}</span>@endif
    {{ $text }}{{ $slot }}
</span>
