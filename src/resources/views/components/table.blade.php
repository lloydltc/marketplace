@props([
    'head'  => null,   // optional: pass thead rows via <x-slot:head>
    'zebra' => false,
])

{{--
    Data table chrome: sticky header, hairline rows, right-align numeric cells with
    `class="text-right tabular-nums"`. On narrow screens it scrolls horizontally;
    screens that need full stacked-card collapse apply that at the call site.
--}}
<div {{ $attributes->class('w-full overflow-x-auto rounded-lg border border-line bg-surface') }}>
    <table class="w-full text-body-sm border-collapse {{ $zebra ? '[&_tbody_tr:nth-child(odd)]:bg-[rgb(var(--bg-surface-2)/0.5)]' : '' }}">
        @isset($head)
            <thead class="sticky top-0 z-sticky bg-surface-2 text-[rgb(var(--text-muted))]">
                <tr class="[&_th]:px-4 [&_th]:py-3 [&_th]:text-left [&_th]:font-semibold [&_th]:text-overline [&_th]:uppercase">
                    {{ $head }}
                </tr>
            </thead>
        @endisset
        <tbody class="text-[rgb(var(--text))] [&_td]:px-4 [&_td]:py-3 [&_tr]:border-t [&_tr]:border-line">
            {{ $slot }}
        </tbody>
    </table>
</div>
