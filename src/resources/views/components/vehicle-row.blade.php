@props([
    'title',
    'vehicles',
    'sponsored' => false,
    'viewAll'   => null,
])

@if ($vehicles->isNotEmpty())
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-h2 text-[rgb(var(--text-strong))] flex items-center gap-2">
                {{ $title }}
                @if ($sponsored)<x-badge variant="featured" :icon="false">Sponsored</x-badge>@endif
            </h2>
            @if ($viewAll)
                <a href="{{ $viewAll }}" class="text-body-sm font-semibold text-[rgb(var(--info))] hover:underline">View all →</a>
            @endif
        </div>

        <div class="flex gap-5 overflow-x-auto sd-rail pb-2 snap-x">
            @foreach ($vehicles as $vehicle)
                <x-vehicle-card :vehicle="$vehicle" :sponsored="$sponsored" class="snap-start shrink-0 w-64 sm:w-72" />
            @endforeach
        </div>
    </section>
@endif
