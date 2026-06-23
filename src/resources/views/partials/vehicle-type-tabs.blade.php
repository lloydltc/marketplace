@php
    $active = request('vehicle_type');
    $counts = $typeCounts ?? [];
    $allTotal = array_sum($counts);
@endphp
<div class="flex flex-wrap gap-2 mb-5">
    <a href="{{ route('vehicles.index', request()->except(['vehicle_type', 'page'])) }}"
       class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-medium transition-colors {{ ! $active ? 'bg-[#1A1A24] text-white' : 'bg-white border border-neutral-200 text-neutral-600 hover:border-neutral-400' }}">
        All
        @if (! empty($counts))
            <span class="text-xs {{ ! $active ? 'text-white/60' : 'text-neutral-400' }} tabular-nums">{{ number_format($allTotal) }}</span>
        @endif
    </a>
    @foreach (config('vehicle_types.types') as $key => $cfg)
        <a href="{{ route('vehicles.index', array_merge(request()->except(['page']), ['vehicle_type' => $key])) }}"
           class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-medium transition-colors {{ $active === $key ? 'bg-[#1A1A24] text-white' : 'bg-white border border-neutral-200 text-neutral-600 hover:border-neutral-400' }}">
            <span>{{ $cfg['icon'] }} {{ $cfg['plural'] }}</span>
            @if (array_key_exists($key, $counts))
                <span class="text-xs {{ $active === $key ? 'text-white/60' : 'text-neutral-400' }} tabular-nums">{{ number_format($counts[$key]) }}</span>
            @endif
        </a>
    @endforeach
</div>
