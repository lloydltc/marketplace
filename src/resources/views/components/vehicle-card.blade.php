@props([
    'vehicle',
    'compare'   => true,    // show the compare toggle overlay
    'sponsored' => false,
])

@php
    $sold = $vehicle->status === 'sold';
    $featured = $vehicle->isFeatured();
    $condition = $vehicle->condition ? ucfirst($vehicle->condition) : null;

    // Spec tiles (icon + value) — only those we actually have.
    $specs = array_values(array_filter([
        $vehicle->mileage      ? ['gauge', number_format($vehicle->mileage) . ' km'] : null,
        $vehicle->year         ? ['calendar', $vehicle->year . ' model'] : null,
        $vehicle->fuel_type    ? ['fuel', ucfirst($vehicle->fuel_type)] : null,
        $vehicle->transmission ? ['gear', ucfirst($vehicle->transmission)] : null,
    ]));
@endphp

<article {{ $attributes->class('group relative flex flex-col bg-surface border border-line rounded-xl overflow-hidden shadow-e1 transition duration-200 ease-standard hover:shadow-e2 hover:-translate-y-0.5 motion-reduce:hover:translate-y-0') }}>
    {{-- media --}}
    <a href="{{ route('vehicles.show', $vehicle) }}" class="relative block aspect-[16/10] bg-surface-2 overflow-hidden">
        <div class="w-full h-full transition-transform duration-500 ease-standard group-hover:scale-[1.03] motion-reduce:group-hover:scale-100">
            <x-listing-thumbnail :cover="$vehicle->coverImage()" :alt="$vehicle->displayTitle()" type="vehicle" />
        </div>

        {{-- status ribbon (top-right) --}}
        <div class="absolute top-2.5 right-2.5 flex flex-col items-end gap-1.5">
            @if ($sold)
                <span class="rounded-full bg-[rgb(var(--danger))] text-white text-caption font-semibold px-2.5 py-0.5">Sold</span>
            @elseif ($condition)
                <span class="rounded-full {{ $vehicle->condition === 'new' ? 'bg-[rgb(var(--info))]' : 'bg-[rgb(var(--bg-sidebar)/0.78)]' }} text-white text-caption font-semibold px-2.5 py-0.5 backdrop-blur-sm">{{ $condition }}</span>
            @endif
            @if ($featured)<x-badge variant="featured" />@endif
            @if ($vehicle->is_recent_import)<x-badge variant="recent-import" />@endif
        </div>
    </a>

    {{-- compare overlay (separate form — not nested in the media link) --}}
    @if ($compare)
        <div class="absolute top-2.5 left-2.5">
            <x-compare-toggle :vehicle="$vehicle" :overlay="true" />
        </div>
    @endif

    {{-- body --}}
    <div class="flex flex-col flex-1 p-4">
        <a href="{{ route('vehicles.show', $vehicle) }}"
           class="text-body font-semibold text-[rgb(var(--text-strong))] leading-snug line-clamp-1 hover:text-brand transition-colors">
            {{ $vehicle->displayTitle() }}
        </a>

        {{-- price + seller --}}
        <div class="mt-2 flex items-end justify-between gap-2">
            <div>
                <x-price :value="$vehicle->primaryPrice()" />
                @if ($vehicle->secondaryPrice())
                    <x-price :value="$vehicle->secondaryPrice()" size="md" class="block !text-caption !font-medium !text-[rgb(var(--text-muted))]" />
                @endif
            </div>
            <span class="shrink-0 inline-flex items-center gap-1 text-caption text-[rgb(var(--text-muted))]">
                @if ($vehicle->ownerIsVerified())
                    <svg class="size-3.5 text-[rgb(var(--success))]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.7 13.3l5-5-1.4-1.4-4.3 4.3-1.6-1.6-1.4 1.4 3 2.3z" clip-rule="evenodd"/></svg>
                @endif
                {{ $vehicle->isListedByVendor() ? 'Dealer' : 'Private' }}
            </span>
        </div>

        {{-- spec tiles --}}
        @if ($specs)
            <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 rounded-lg bg-surface-2 p-3">
                @foreach ($specs as [$icon, $label])
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-[rgb(var(--text-muted))] shrink-0">
                            @switch($icon)
                                @case('gauge')<svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.5 14a8.5 8.5 0 1117 0"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l4-2.5"/></svg>@break
                                @case('calendar')<svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3.5" y="4.5" width="17" height="16" rx="2"/><path stroke-linecap="round" d="M16 3v3M8 3v3M3.5 9.5h17"/></svg>@break
                                @case('fuel')<svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20V5a2 2 0 012-2h5a2 2 0 012 2v15M3 20h11"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 9h3l2 2v6a1.5 1.5 0 003 0V8.5L18.5 5"/></svg>@break
                                @case('gear')<svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" d="M12 4V2.5M12 21.5V20M4 12H2.5M21.5 12H20M6.3 6.3L5.2 5.2M18.8 18.8l-1.1-1.1M17.7 6.3l1.1-1.1M5.2 18.8l1.1-1.1"/></svg>@break
                            @endswitch
                        </span>
                        <dd class="text-caption text-[rgb(var(--text))] truncate">{{ $label }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </div>
</article>
