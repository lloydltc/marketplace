@props([
    'value' => null,      // pre-formatted price string, e.g. "USD 45,500.00"
    'poa'   => false,     // price on application (or pass an empty value)
    'size'  => 'lg',      // xl | lg | md — typographic size token
])

@php
    $sizeCls = [
        'xl' => 'text-price',                          // 1.5rem/700 — detail pages
        'lg' => 'text-base font-bold leading-tight',   // 1rem — cards
        'md' => 'text-body-sm font-semibold',
    ][$size] ?? 'text-base font-bold leading-tight';

    // Display layer only (model strings stay "USD …" for the rest of the app):
    // show "$" for USD and drop the trailing ".00" so prices read cleanly.
    $isPoa = $poa || blank($value) || in_array(trim((string) $value), ['Price on application', 'Price on request'], true);
    $display = $isPoa ? null : $value;
    if ($display) {
        $display = preg_replace('/^USD\s*/', '$', $display);
        $display = preg_replace('/\.00\b/', '', $display);
    }
@endphp

<span {{ $attributes->class("$sizeCls tabular-nums text-ink") }}>
    @if ($display)
        {{ $display }}
    @else
        <span class="text-body-sm font-medium text-muted">Price on application</span>
    @endif
</span>
