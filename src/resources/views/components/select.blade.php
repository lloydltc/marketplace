@props([
    'label'    => null,
    'name'     => null,
    'id'       => null,
    'hint'     => null,
    'error'    => null,
    'required' => false,
])

@php
    $id = $id ?? $name;
    $error = $error ?? ($name ? ($errors->first($name) ?: null) : null);
    $hintId = $hint ? "$id-hint" : null;
    $errId  = $error ? "$id-error" : null;
    $describedBy = trim(implode(' ', array_filter([$hintId, $errId]))) ?: null;

    $control = 'w-full h-11 pl-3.5 pr-9 rounded-md bg-surface text-[rgb(var(--text-strong))] '
        . 'border appearance-none bg-no-repeat transition-colors duration-150 '
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

    <div class="relative">
        <select id="{{ $id }}" @if ($name) name="{{ $name }}" @endif
            @if ($required) required @endif
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->class($control) }}>
            {{ $slot }}
        </select>
        <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 size-4 text-[rgb(var(--text-muted))]"
             viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 8l4 4 4-4" />
        </svg>
    </div>

    @if ($hint && ! $error)
        <p id="{{ $hintId }}" class="mt-1.5 text-caption text-[rgb(var(--text-muted))]">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $errId }}" class="mt-1.5 inline-flex items-center gap-1 text-caption text-[rgb(var(--danger))]">
            <span aria-hidden="true">!</span> {{ $error }}
        </p>
    @endif
</div>
