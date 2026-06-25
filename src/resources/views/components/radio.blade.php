@props([
    'label' => null,
    'name'  => null,
    'id'    => null,
    'value' => null,
])

@php $id = $id ?? ($name && $value !== null ? "$name-$value" : $name); @endphp

<label for="{{ $id }}" class="inline-flex items-start gap-2.5 cursor-pointer select-none">
    <input type="radio" id="{{ $id }}" @if ($name) name="{{ $name }}" @endif @if ($value !== null) value="{{ $value }}" @endif
        {{ $attributes->class(
            'mt-0.5 size-5 shrink-0 border-strong bg-surface '
            . 'text-brand accent-[rgb(var(--brand))] '
            . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2'
        ) }}>
    @if ($label || ! $slot->isEmpty())
        <span class="text-body-sm text-[rgb(var(--text))] leading-snug">{{ $label }}{{ $slot }}</span>
    @endif
</label>
