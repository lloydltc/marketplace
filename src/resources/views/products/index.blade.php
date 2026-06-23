<x-layouts.app>
    <x-slot:title>Browse Products</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-neutral-900">Browse Products</h1>
            <p class="text-sm text-neutral-500 mt-1">Find spare parts, accessories, tools, and more.</p>
        </div>

        {{-- Search & Filters --}}
        <form method="GET" class="flex flex-wrap gap-3 mb-3">
            <x-search-autocomplete name="q" :endpoint="route('search.products')"
                                   :value="request('q')" placeholder="Search products…" />

            <select name="category"
                    class="border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                <option value="">All categories</option>
                @foreach ($categories as $root)
                    <optgroup label="{{ $root->icon }} {{ $root->name }}">
                        @foreach ($root->children as $child)
                            <option value="{{ $child->id }}" @selected(request('category') === $child->id)>
                                {{ $child->name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>

            <select name="fulfilment"
                    class="border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                <option value="">Any fulfilment</option>
                <option value="fbs"    @selected(request('fulfilment') === 'fbs')>Fulfilled by Salma</option>
                <option value="vendor" @selected(request('fulfilment') === 'vendor')>Vendor-fulfilled</option>
            </select>

            <select name="sort"
                    class="border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                <option value="latest"     @selected(request('sort', 'latest') === 'latest')>Latest</option>
                <option value="price_asc"  @selected(request('sort') === 'price_asc')>Price: Low to High</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: High to Low</option>
                <option value="rating"     @selected(request('sort') === 'rating')>Top Rated</option>
            </select>

            <button type="submit"
                    class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-5 py-2 rounded-lg text-sm transition-colors">
                Search
            </button>
        </form>

        {{-- Save current search (signed-in users, active filters only) --}}
        <div class="mb-8 min-h-[2rem]">
            <x-save-search type="products" :active="request()->hasAny(['q', 'category', 'fulfilment', 'min_price', 'max_price'])" />
        </div>

        @if ($products->isEmpty())
            <div class="py-16 space-y-6">
                <p class="text-neutral-500 text-sm text-center">No products found. Try a different search or category.</p>
                <x-rfq-cta context="products" :query="request('q', '')" />
                <div class="text-center">
                    <a href="{{ route('products.index') }}" class="text-sm text-[#3DB8E8] hover:underline">Clear filters</a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @foreach ($products as $product)
                    <a href="{{ route('products.show', $product) }}"
                       class="bg-white border border-neutral-200 rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden group">
                        <div class="bg-neutral-100 h-40 flex items-center justify-center overflow-hidden">
                            <x-listing-thumbnail :cover="$product->coverImage()" :alt="$product->title" type="product" />
                        </div>
                        <div class="p-4">
                            <div class="text-xs text-neutral-400 mb-1">{{ $product->category?->name }}</div>
                            <h3 class="text-sm font-semibold text-neutral-900 line-clamp-2 group-hover:text-[#F0A820] transition-colors">
                                {{ $product->title }}
                            </h3>
                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-sm font-bold text-neutral-900 tabular-nums">
                                    {{ $product->primaryPrice() }}
                                </span>
                                <span class="text-xs text-neutral-400 tabular-nums">
                                    {{ $product->convertedZwl() }}
                                </span>
                            </div>
                            <div class="mt-2 text-xs text-neutral-400">
                                {{ $product->vendor?->name }} · {{ $product->quantity }} in stock
                            </div>
                            <div class="mt-1">
                                <x-unverified-badge :verified="$product->vendor?->isApproved() ?? true" />
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->withQueryString()->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
