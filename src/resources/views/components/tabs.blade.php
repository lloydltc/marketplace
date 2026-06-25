@props([
    'tabs'    => [],     // ['cars' => 'Cars', 'bikes' => 'Bikes', …]
    'default' => null,
])

@php $default = $default ?? array_key_first($tabs); $keys = array_keys($tabs); @endphp

{{--
    Segmented tab control. The `tab` Alpine variable is in scope for $slot, so
    panels are written by the caller as: <div x-show="tab === 'cars'" role="tabpanel">…</div>
--}}
<div x-data="{ tab: @js($default), keys: @js($keys) }" {{ $attributes }}>
    <div role="tablist" aria-orientation="horizontal"
         class="inline-flex items-center gap-1 rounded-full bg-surface-2 p-1"
         @keydown.right.prevent="tab = keys[(keys.indexOf(tab) + 1) % keys.length]"
         @keydown.left.prevent="tab = keys[(keys.indexOf(tab) - 1 + keys.length) % keys.length]">
        @foreach ($tabs as $key => $label)
            <button type="button" role="tab"
                    :aria-selected="(tab === @js($key)).toString()"
                    :tabindex="tab === @js($key) ? 0 : -1"
                    @click="tab = @js($key)"
                    :class="tab === @js($key)
                        ? 'bg-brand text-on-brand'
                        : 'text-[rgb(var(--text-muted))] hover:text-[rgb(var(--text))]'"
                    class="h-9 px-4 rounded-full text-body-sm font-medium transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if (! $slot->isEmpty())<div class="mt-5">{{ $slot }}</div>@endif
</div>
