@props([
    'steps' => [],        // ['Details', 'Specs', 'Pricing', 'Photos']
    'start' => 0,         // initial step (server can jump to the first errored step)
    'hint'  => null,      // muted note in the action bar
])

@php $count = count($steps); @endphp

{{--
    Multi-step form shell. Place INSIDE a <form>. Panels are written by the caller
    as <x-card x-show="step === 0" x-cloak>…</x-card>; the `step` Alpine var is in
    scope for the slot. Back/Next don't submit; the final-step submit buttons go in
    the <x-slot:actions> and show only on the last step. (No long-scroll forms.)
--}}
<div x-data="{ step: {{ (int) $start }}, count: {{ $count }} }" {{ $attributes->class('space-y-6') }}>
    {{-- Step indicator --}}
    <ol class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto sd-rail pb-1">
        @foreach ($steps as $i => $label)
            <li class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                <button type="button" @click="step = {{ $i }}"
                        :class="step === {{ $i }} ? 'bg-brand text-on-brand' : (step > {{ $i }} ? 'bg-[rgb(var(--success))] text-white' : 'bg-surface-2 text-muted')"
                        class="size-7 rounded-full text-caption font-bold grid place-items-center transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                        :aria-current="step === {{ $i }} ? 'step' : false">
                    <span x-show="step <= {{ $i }}">{{ $i + 1 }}</span>
                    <svg x-show="step > {{ $i }}" x-cloak class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 10l4 4 8-8"/></svg>
                </button>
                <span class="text-body-sm font-medium hidden sm:block whitespace-nowrap" :class="step === {{ $i }} ? 'text-ink' : 'text-muted'">{{ $label }}</span>
                @unless ($loop->last)<span class="w-5 sm:w-8 h-px bg-[rgb(var(--border-strong))]"></span>@endunless
            </li>
        @endforeach
    </ol>

    {{-- Panels --}}
    <div>{{ $slot }}</div>

    {{-- Sticky action bar --}}
    <div class="sticky bottom-0 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-3 bg-[rgb(var(--bg-surface)/0.95)] backdrop-blur border-t border-line flex flex-wrap items-center gap-3 z-sticky">
        <button type="button" x-show="step > 0" x-cloak @click="step--; window.scrollTo({ top: 0, behavior: 'smooth' })"
                class="inline-flex items-center gap-1.5 h-11 px-5 rounded-md border border-strong text-[rgb(var(--text))] hover:bg-surface-2 font-semibold text-body-sm transition-colors">
            <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5l-5 5 5 5"/></svg>
            Back
        </button>
        <button type="button" x-show="step < count - 1" @click="step++; window.scrollTo({ top: 0, behavior: 'smooth' })"
                class="inline-flex items-center gap-1.5 h-11 px-6 rounded-md bg-brand hover:bg-brand-hover text-on-brand font-semibold text-body-sm transition-colors">
            Next
            <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5l5 5-5 5"/></svg>
        </button>

        {{-- Final-step submit actions --}}
        <div x-show="step === count - 1" x-cloak class="flex flex-wrap items-center gap-3">{{ $actions ?? '' }}</div>

        @if ($hint)<span class="text-caption text-muted ml-auto hidden sm:block">{{ $hint }}</span>@endif
    </div>
</div>
