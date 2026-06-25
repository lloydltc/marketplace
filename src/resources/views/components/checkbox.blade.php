@props([
    'label' => null,
    'name'  => null,
    'id'    => null,
    'hint'  => null,
])

@php $id = $id ?? $name; $hintId = $hint ? "$id-hint" : null; @endphp

<label for="{{ $id }}" class="inline-flex items-start gap-2.5 cursor-pointer select-none">
    <input type="checkbox" id="{{ $id }}" @if ($name) name="{{ $name }}" @endif
        @if ($hintId) aria-describedby="{{ $hintId }}" @endif
        {{ $attributes->class(
            'mt-0.5 size-5 shrink-0 rounded-[6px] border-strong bg-surface '
            . 'text-brand accent-[rgb(var(--brand))] '
            . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2'
        ) }}>
    @if ($label || ! $slot->isEmpty())
        <span class="text-body-sm text-[rgb(var(--text))] leading-snug">
            {{ $label }}{{ $slot }}
            @if ($hint)<span id="{{ $hintId }}" class="block text-caption text-[rgb(var(--text-muted))]">{{ $hint }}</span>@endif
        </span>
    @endif
</label>
