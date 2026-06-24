@props([
    'vehicle',
    'class' => '',
])

@php $inList = app(\App\Support\CompareList::class)->has($vehicle->id); @endphp

@if ($inList)
    <form method="POST" action="{{ route('compare.remove', $vehicle) }}" {{ $attributes }}>
        @csrf @method('DELETE')
        <button type="submit"
                class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-[#F0A820] bg-[#F0A820]/10 text-[#B5790F] transition-colors {{ $class }}">
            ⇄ In compare
        </button>
    </form>
@else
    <form method="POST" action="{{ route('compare.add', $vehicle) }}" {{ $attributes }}>
        @csrf
        <button type="submit"
                class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-neutral-200 text-neutral-600 hover:border-neutral-400 transition-colors {{ $class }}">
            ⇄ Compare
        </button>
    </form>
@endif
