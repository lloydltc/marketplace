@props([
    'title',
    'vehicles',
    'sponsored' => false,
    'viewAll' => null,
])

@if ($vehicles->isNotEmpty())
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-neutral-900 flex items-center gap-2">
                {{ $title }}
                @if ($sponsored)
                    <span class="text-[10px] font-semibold uppercase tracking-wide bg-[#F0A820]/15 text-[#B5790F] px-2 py-0.5 rounded-full">Sponsored</span>
                @endif
            </h2>
            @if ($viewAll)
                <a href="{{ $viewAll }}" class="text-sm text-[#3DB8E8] hover:underline">View all →</a>
            @endif
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
            @foreach ($vehicles as $vehicle)
                <a href="{{ route('vehicles.show', $vehicle) }}"
                   class="group bg-white border border-neutral-200 rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">
                    <div class="aspect-video bg-neutral-100 flex items-center justify-center overflow-hidden">
                        <x-listing-thumbnail :cover="$vehicle->coverImage()" :alt="$vehicle->displayTitle()" type="vehicle" />
                    </div>
                    <div class="p-4 flex flex-col flex-1">
                        @if ($sponsored || $vehicle->isFeatured())
                            <span class="self-start mb-2 text-xs font-semibold bg-[#F0A820]/15 text-[#B5790F] px-2 py-0.5 rounded-full">★ Featured</span>
                        @endif
                        <h3 class="text-sm font-semibold text-neutral-900 group-hover:text-[#F0A820] transition-colors leading-snug mb-2">
                            {{ $vehicle->displayTitle() }}
                        </h3>
                        <div class="mt-auto text-base font-bold text-neutral-900 tabular-nums">
                            {{ $vehicle->primaryPrice() }}
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endif
