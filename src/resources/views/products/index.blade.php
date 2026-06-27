<x-layouts.app>
    <x-slot:title>Parts &amp; accessories</x-slot:title>
    <x-slot:metaDescription>Spare parts, accessories, and tools from verified vendors on SalmaDrive.</x-slot:metaDescription>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <header class="mb-6">
            <h1 class="text-h1 text-ink">Parts &amp; accessories</h1>
            <p class="text-body-sm text-muted mt-1">Find spare parts, accessories, tools, and more.</p>
        </header>

        {{-- Category quick-rail --}}
        @if ($categories->isNotEmpty())
            <div class="flex items-center gap-2.5 overflow-x-auto sd-rail pb-1 mb-5">
                <a href="{{ route('products.index') }}"
                   class="shrink-0 inline-flex items-center px-4 py-2 rounded-full text-body-sm font-medium transition-colors {{ ! request('category') ? 'bg-sidebar text-white' : 'bg-surface border border-line text-muted hover:border-strong' }}">All parts</a>
                @foreach ($categories as $root)
                    <a href="{{ route('products.index', ['category' => $root->id]) }}"
                       class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-body-sm font-medium transition-colors bg-surface border border-line text-[rgb(var(--text))] hover:border-brand hover:text-brand">
                        @if ($root->icon)<span aria-hidden="true">{{ $root->icon }}</span>@endif {{ $root->name }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Filter bar --}}
        <form method="GET" class="flex flex-wrap items-end gap-3 mb-3">
            <div class="flex-1 min-w-[12rem]">
                <x-search-autocomplete name="q" :endpoint="route('search.products')"
                                       :value="request('q')" placeholder="Search parts…" />
            </div>

            <x-select name="category" class="!w-auto min-w-[11rem]">
                <option value="">All categories</option>
                @foreach ($categories as $root)
                    <optgroup label="{{ $root->icon }} {{ $root->name }}">
                        @foreach ($root->children as $child)
                            <option value="{{ $child->id }}" @selected(request('category') === $child->id)>{{ $child->name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </x-select>

            <x-select name="fulfilment" class="!w-auto min-w-[11rem]">
                <option value="">Any fulfilment</option>
                <option value="fbs" @selected(request('fulfilment') === 'fbs')>Fulfilled by Salma</option>
                <option value="vendor" @selected(request('fulfilment') === 'vendor')>Vendor-fulfilled</option>
            </x-select>

            <x-select name="sort" class="!w-auto min-w-[10rem]">
                <option value="latest" @selected(request('sort', 'latest') === 'latest')>Latest</option>
                <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: low to high</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: high to low</option>
                <option value="rating" @selected(request('sort') === 'rating')>Top rated</option>
            </x-select>

            <x-button type="submit">Search</x-button>
        </form>

        <div class="mb-8 min-h-[2rem]">
            <x-save-search type="products" :active="request()->hasAny(['q', 'category', 'fulfilment', 'min_price', 'max_price'])" />
        </div>

        @if ($products->isEmpty())
            <div class="py-10 space-y-6">
                <x-empty title="No parts found" message="Try a different search or category — or let vendors quote you." />
                <x-rfq-cta context="products" :query="request('q', '')" />
                <div class="text-center">
                    <a href="{{ route('products.index') }}" class="text-body-sm text-[rgb(var(--info))] hover:underline">Clear filters</a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach ($products as $product)
                    <x-part-card :product="$product" />
                @endforeach
            </div>
            <x-pagination :paginator="$products->withQueryString()" class="mt-8" />
        @endif
    </div>
</x-layouts.app>
