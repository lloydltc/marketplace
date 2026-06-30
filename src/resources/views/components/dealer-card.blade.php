@props([
    'dealer',
    'featured' => false,
])

<a href="{{ $dealer->storefrontUrl() }}"
   {{ $attributes->class('group flex flex-col bg-surface border rounded-lg shadow-e1 p-5 transition duration-200 ease-standard hover:shadow-e2 hover:-translate-y-0.5 motion-reduce:hover:translate-y-0 '
       . ($featured ? 'border-[rgb(var(--brand)/0.5)]' : 'border-line')) }}>
    <div class="flex items-center gap-3 mb-3">
        <div class="size-12 rounded-md bg-surface-2 grid place-items-center overflow-hidden shrink-0">
            @if ($dealer->logoUrl())
                <img src="{{ $dealer->logoUrl() }}" alt="{{ $dealer->name }}" class="w-full h-full object-cover">
            @else
                <span class="text-h4 font-bold text-[rgb(var(--text-muted))]">{{ Str::upper(Str::substr($dealer->name, 0, 1)) }}</span>
            @endif
        </div>
        <div class="min-w-0">
            <h3 class="text-h4 text-[rgb(var(--text-strong))] group-hover:text-brand transition-colors truncate">{{ $dealer->name }}</h3>
            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                @if ($dealer->verification_tier)
                    <x-trust-badge :vendor="$dealer" size="xs" />
                @else
                    <x-badge variant="verified" />
                @endif
                @if ($featured || $dealer->isFeaturedDealer())
                    <x-badge variant="featured" />
                @endif
            </div>
        </div>
    </div>

    @if ($dealer->description)
        <p class="text-body-sm text-[rgb(var(--text-muted))] line-clamp-2 mb-3">{{ $dealer->description }}</p>
    @endif

    <div class="mt-auto flex items-center gap-4 text-caption text-[rgb(var(--text-muted))] tabular-nums">
        <span><strong class="text-[rgb(var(--text))]">{{ number_format($dealer->live_vehicles_count ?? 0) }}</strong> vehicles</span>
        <span><strong class="text-[rgb(var(--text))]">{{ number_format($dealer->live_products_count ?? 0) }}</strong> parts</span>
    </div>
</a>
