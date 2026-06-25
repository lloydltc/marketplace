@props([
    'label'   => null,
    'name'    => null,
    'checked' => false,
    'value'   => '1',     // value submitted when on
])

@php $id = $name ? "$name-toggle" : null; @endphp

<div x-data="{ on: @js((bool) $checked) }" class="inline-flex items-center gap-3">
    @if ($name)
        <input type="hidden" name="{{ $name }}" :value="on ? '{{ $value }}' : ''">
    @endif
    <button type="button" role="switch" @if ($id) id="{{ $id }}" @endif
        :aria-checked="on.toString()"
        @if ($label) aria-label="{{ $label }}" @endif
        @click="on = !on"
        {{ $attributes->class('relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-200 ease-standard focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2') }}
        :class="on ? 'bg-brand' : 'bg-[rgb(var(--border-strong))]'">
        <span class="inline-block size-5 transform rounded-full bg-white shadow-e1 transition-transform duration-200 ease-standard"
              :class="on ? 'translate-x-5' : 'translate-x-0.5'"></span>
    </button>
    @if ($label)
        <label @if ($id) for="{{ $id }}" @endif class="text-body-sm text-[rgb(var(--text))] cursor-pointer">{{ $label }}</label>
    @endif
</div>
