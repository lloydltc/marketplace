<x-layouts.app>
    <x-slot:title>{{ $vendor->name }}</x-slot:title>
    <x-slot:metaDescription>{{ $vendor->name }} on SalmaDrive — {{ $vendor->description ? Str::limit($vendor->description, 140) : 'verified dealer inventory.' }}</x-slot:metaDescription>

    {{-- Storefront header --}}
    <div class="bg-[#1A1A24]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <a href="{{ route('dealers.index') }}" class="text-sm text-neutral-400 hover:text-white">← All dealers</a>
            <div class="flex flex-col sm:flex-row sm:items-center gap-5 mt-4">
                <div class="w-20 h-20 rounded-xl bg-white/10 flex items-center justify-center overflow-hidden shrink-0">
                    @if ($vendor->logoUrl())
                        <img src="{{ $vendor->logoUrl() }}" alt="{{ $vendor->name }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-2xl font-bold text-white/60">{{ Str::upper(Str::substr($vendor->name, 0, 1)) }}</span>
                    @endif
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-2xl font-bold text-white">{{ $vendor->name }}</h1>
                        <span class="text-xs font-medium bg-[#2EBD7A]/20 text-[#2EBD7A] px-2 py-0.5 rounded-full">✓ Verified dealer</span>
                        @if ($vendor->isFeaturedDealer())
                            <span class="text-xs font-semibold bg-[#F0A820]/20 text-[#F0A820] px-2 py-0.5 rounded-full">★ Featured</span>
                        @endif
                    </div>
                    @if ($vendor->description)
                        <p class="text-sm text-neutral-400 mt-2 max-w-2xl">{{ $vendor->description }}</p>
                    @endif
                    <div class="flex flex-wrap gap-x-5 gap-y-1 mt-3 text-sm text-neutral-400">
                        @if ($vendor->phone)
                            <a href="tel:{{ $vendor->phone }}" class="hover:text-white">📞 {{ $vendor->phone }}</a>
                        @endif
                        @if ($vendor->address)
                            <span>📍 {{ $vendor->address }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-12">

        {{-- Vehicles --}}
        <section>
            <h2 class="text-xl font-semibold text-neutral-900 mb-5">Vehicles ({{ $vehicles->total() }})</h2>
            @if ($vehicles->isEmpty())
                <p class="text-sm text-neutral-500">No vehicles listed right now.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    @foreach ($vehicles as $vehicle)
                        <a href="{{ route('vehicles.show', $vehicle) }}"
                           class="group bg-white border border-neutral-200 rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">
                            <div class="aspect-video bg-neutral-100 flex items-center justify-center overflow-hidden">
                                <x-listing-thumbnail :cover="$vehicle->coverImage()" :alt="$vehicle->displayTitle()" type="vehicle" />
                            </div>
                            <div class="p-4 flex flex-col flex-1">
                                @if ($vehicle->isFeatured())
                                    <span class="self-start mb-2 text-xs font-semibold bg-[#F0A820]/15 text-[#B5790F] px-2 py-0.5 rounded-full">★ Featured</span>
                                @endif
                                <h3 class="text-sm font-semibold text-neutral-900 group-hover:text-[#F0A820] transition-colors leading-snug mb-2">{{ $vehicle->displayTitle() }}</h3>
                                <div class="mt-auto text-base font-bold text-neutral-900 tabular-nums">{{ $vehicle->primaryPrice() }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="mt-6">{{ $vehicles->links() }}</div>
            @endif
        </section>

        {{-- Parts & accessories --}}
        <section>
            <h2 class="text-xl font-semibold text-neutral-900 mb-5">Parts & accessories ({{ $products->total() }})</h2>
            @if ($products->isEmpty())
                <p class="text-sm text-neutral-500">No parts listed right now.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    @foreach ($products as $product)
                        <a href="{{ route('products.show', $product) }}"
                           class="bg-white border border-neutral-200 rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden group">
                            <div class="bg-neutral-100 h-40 flex items-center justify-center overflow-hidden">
                                <x-listing-thumbnail :cover="$product->coverImage()" :alt="$product->title" type="product" />
                            </div>
                            <div class="p-4">
                                <div class="text-xs text-neutral-400 mb-1">{{ $product->category?->name }}</div>
                                <h3 class="text-sm font-semibold text-neutral-900 line-clamp-2 group-hover:text-[#F0A820] transition-colors">{{ $product->title }}</h3>
                                <div class="mt-3 text-sm font-bold text-neutral-900 tabular-nums">{{ $product->primaryPrice() }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="mt-6">{{ $products->links() }}</div>
            @endif
        </section>
    </div>
</x-layouts.app>
