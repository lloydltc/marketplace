@props([
    'dealer',
    'featured' => false,
])

<a href="{{ $dealer->storefrontUrl() }}"
   class="group bg-white border {{ $featured ? 'border-[#F0A820]/50' : 'border-neutral-200' }} rounded-xl shadow-sm hover:shadow-md transition-shadow p-5 flex flex-col">
    <div class="flex items-center gap-3 mb-3">
        <div class="w-12 h-12 rounded-lg bg-neutral-100 flex items-center justify-center overflow-hidden shrink-0">
            @if ($dealer->logoUrl())
                <img src="{{ $dealer->logoUrl() }}" alt="{{ $dealer->name }}" class="w-full h-full object-cover">
            @else
                <span class="text-lg font-bold text-neutral-400">{{ Str::upper(Str::substr($dealer->name, 0, 1)) }}</span>
            @endif
        </div>
        <div class="min-w-0">
            <h3 class="font-semibold text-neutral-900 group-hover:text-[#F0A820] transition-colors truncate">{{ $dealer->name }}</h3>
            <div class="flex items-center gap-1.5 mt-0.5">
                <span class="text-[11px] font-medium bg-[#2EBD7A]/15 text-[#1B8F5A] px-1.5 py-0.5 rounded-full">✓ Verified</span>
                @if ($featured || $dealer->isFeaturedDealer())
                    <span class="text-[11px] font-semibold bg-[#F0A820]/15 text-[#B5790F] px-1.5 py-0.5 rounded-full">★ Featured</span>
                @endif
            </div>
        </div>
    </div>

    @if ($dealer->description)
        <p class="text-sm text-neutral-500 line-clamp-2 mb-3">{{ $dealer->description }}</p>
    @endif

    <div class="mt-auto flex items-center gap-4 text-xs text-neutral-500 tabular-nums">
        <span>{{ number_format($dealer->live_vehicles_count ?? 0) }} vehicles</span>
        <span>{{ number_format($dealer->live_products_count ?? 0) }} parts</span>
    </div>
</a>
