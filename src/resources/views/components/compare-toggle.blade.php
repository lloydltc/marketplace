@props([
    'vehicle',
    'overlay' => false,   // true → translucent pill for use over a photo
])

@php
    $inList = app(\App\Support\CompareList::class)->has($vehicle->id);
    $shell = $overlay
        ? 'bg-[rgb(var(--bg-sidebar)/0.72)] text-white backdrop-blur-sm'
        : 'bg-surface border border-base text-[rgb(var(--text))]';
@endphp

<form method="POST" action="{{ route($inList ? 'compare.remove' : 'compare.add', $vehicle) }}" {{ $attributes }}>
    @csrf
    @if ($inList) @method('DELETE') @endif
    <button type="submit" :aria-pressed="@js($inList)"
            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-caption font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand {{ $shell }}">
        <span class="grid place-items-center size-4 rounded-[4px] border {{ $inList ? 'bg-brand border-brand text-on-brand' : 'border-current' }}">
            @if ($inList)
                <svg class="size-3" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 10l4 4 8-8" /></svg>
            @endif
        </span>
        {{ $inList ? 'Comparing' : 'Compare' }}
    </button>
</form>
