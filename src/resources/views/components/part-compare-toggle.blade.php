@props(['part'])

@php $inList = app(\App\Support\PartCompareList::class)->has($part->id); @endphp

<form method="POST" action="{{ route($inList ? 'parts.compare.remove' : 'parts.compare.add', $part) }}" {{ $attributes }}>
    @csrf
    @if ($inList) @method('DELETE') @endif
    <button type="submit"
            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-caption font-medium transition-colors {{ $inList ? 'bg-brand border border-brand text-on-brand' : 'bg-surface border border-line text-[rgb(var(--text))]' }}">
        ⇄ {{ $inList ? 'Comparing' : 'Compare' }}
    </button>
</form>
