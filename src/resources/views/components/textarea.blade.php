@props([
    'label'    => null,
    'name'     => null,
    'id'       => null,
    'hint'     => null,
    'error'    => null,
    'rows'     => 4,
    'required' => false,
])

@php
    $id = $id ?? $name;
    $error = $error ?? ($name ? ($errors->first($name) ?: null) : null);
    $hintId = $hint ? "$id-hint" : null;
    $errId  = $error ? "$id-error" : null;
    $describedBy = trim(implode(' ', array_filter([$hintId, $errId]))) ?: null;

    $control = 'w-full px-3.5 py-2.5 rounded-md bg-surface text-[rgb(var(--text-strong))] '
        . 'placeholder:text-[rgb(var(--text-muted))] border transition-colors duration-150 '
        . 'focus-visible:outline-none focus:ring-2 focus:ring-brand focus:border-brand '
        . ($error
            ? 'border-[rgb(var(--danger))] focus:ring-[rgb(var(--danger))] focus:border-[rgb(var(--danger))]'
            : 'border-strong');
@endphp

<div class="w-full">
    @if ($label)
        <label for="{{ $id }}" class="block mb-1.5 text-body-sm font-medium text-[rgb(var(--text))]">
            {{ $label }}@if ($required)<span class="text-[rgb(var(--danger))]" aria-hidden="true"> *</span>@endif
        </label>
    @endif

    <textarea id="{{ $id }}" @if ($name) name="{{ $name }}" @endif rows="{{ $rows }}"
        @if ($required) required @endif
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class($control) }}>{{ $slot }}</textarea>

    @if ($hint && ! $error)
        <p id="{{ $hintId }}" class="mt-1.5 text-caption text-[rgb(var(--text-muted))]">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $errId }}" class="mt-1.5 inline-flex items-center gap-1 text-caption text-[rgb(var(--danger))]">
            <span aria-hidden="true">!</span> {{ $error }}
        </p>
    @endif
</div>
