@php
    $active = request('vehicle_type');
    $counts = $typeCounts ?? [];
    $allTotal = array_sum($counts);
    $on  = 'bg-[rgb(var(--bg-sidebar))] text-white';
    $off = 'bg-surface border border-line text-[rgb(var(--text-muted))] hover:border-strong hover:text-[rgb(var(--text))]';
@endphp
<div class="flex flex-wrap gap-2 mb-5">
    <a href="{{ route('vehicles.index', request()->except(['vehicle_type', 'page'])) }}"
       class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-body-sm font-medium transition-colors {{ ! $active ? $on : $off }}">
        All
        @if (! empty($counts))
            <span class="text-caption {{ ! $active ? 'text-white/60' : 'text-[rgb(var(--text-muted))]' }} tabular-nums">{{ number_format($allTotal) }}</span>
        @endif
    </a>
    @foreach (config('vehicle_types.types') as $key => $cfg)
        <a href="{{ route('vehicles.index', array_merge(request()->except(['page']), ['vehicle_type' => $key])) }}"
           class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-body-sm font-medium transition-colors {{ $active === $key ? $on : $off }}">
            <span>{{ $cfg['icon'] }} {{ $cfg['plural'] }}</span>
            @if (array_key_exists($key, $counts))
                <span class="text-caption {{ $active === $key ? 'text-white/60' : 'text-[rgb(var(--text-muted))]' }} tabular-nums">{{ number_format($counts[$key]) }}</span>
            @endif
        </a>
    @endforeach
</div>
