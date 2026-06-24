@php $compareCount = app(\App\Support\CompareList::class)->count(); @endphp

@if ($compareCount > 0)
    <div class="fixed bottom-4 inset-x-0 z-40 flex justify-center px-4 pointer-events-none">
        <div class="pointer-events-auto flex items-center gap-4 bg-[#1A1A24] text-white rounded-full shadow-lg pl-5 pr-3 py-2.5">
            <span class="text-sm font-medium">
                {{ $compareCount }} {{ Str::plural('vehicle', $compareCount) }} to compare
            </span>
            <a href="{{ route('compare.show') }}"
               class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] text-sm font-semibold px-4 py-1.5 rounded-full transition-colors">
                Compare
            </a>
            <form method="POST" action="{{ route('compare.clear') }}">
                @csrf @method('DELETE')
                <button type="submit" class="text-neutral-400 hover:text-white text-sm" aria-label="Clear compare list">✕</button>
            </form>
        </div>
    </div>
@endif
