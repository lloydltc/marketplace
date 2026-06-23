@php $active = request('vehicle_type'); @endphp
<div class="flex flex-wrap gap-2 mb-5">
    <a href="{{ route('vehicles.index', request()->except(['vehicle_type', 'page'])) }}"
       class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors {{ ! $active ? 'bg-[#1A1A24] text-white' : 'bg-white border border-neutral-200 text-neutral-600 hover:border-neutral-400' }}">
        All
    </a>
    @foreach (config('vehicle_types.types') as $key => $cfg)
        <a href="{{ route('vehicles.index', array_merge(request()->except(['page']), ['vehicle_type' => $key])) }}"
           class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors {{ $active === $key ? 'bg-[#1A1A24] text-white' : 'bg-white border border-neutral-200 text-neutral-600 hover:border-neutral-400' }}">
            {{ $cfg['icon'] }} {{ $cfg['plural'] }}
        </a>
    @endforeach
</div>
