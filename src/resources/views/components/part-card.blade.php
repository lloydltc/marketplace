@props([
    'product',
])

@php $inStock = ($product->quantity ?? 0) > 0; @endphp

<article {{ $attributes->class('group relative flex flex-col bg-surface border border-base rounded-xl overflow-hidden shadow-e1 transition duration-200 ease-standard hover:shadow-e2 hover:-translate-y-0.5 motion-reduce:hover:translate-y-0') }}>
    <a href="{{ route('products.show', $product) }}" class="relative block aspect-square bg-surface-2 overflow-hidden grid place-items-center">
        <div class="w-full h-full transition-transform duration-500 ease-standard group-hover:scale-[1.03] motion-reduce:group-hover:scale-100 grid place-items-center">
            <x-listing-thumbnail :cover="$product->coverImage()" :alt="$product->title" type="product" />
        </div>
        @unless ($inStock)
            <span class="absolute top-2.5 right-2.5 rounded-full bg-[rgb(var(--bg-sidebar)/0.78)] text-white text-caption font-semibold px-2.5 py-0.5 backdrop-blur-sm">Out of stock</span>
        @endunless
    </a>

    <div class="flex flex-col flex-1 p-4">
        @if ($product->category?->name)
            <span class="text-overline uppercase text-[rgb(var(--text-muted))]">{{ $product->category->name }}</span>
        @endif

        <a href="{{ route('products.show', $product) }}"
           class="mt-1 text-body-sm font-semibold text-[rgb(var(--text-strong))] leading-snug line-clamp-2 hover:text-brand transition-colors">
            {{ $product->title }}
        </a>

        <div class="mt-auto pt-3">
            <div class="rounded-lg bg-surface-2 p-3 flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <x-price :value="$product->primaryPrice()" />
                    @if ($product->vendor?->name)
                        <p class="text-caption text-[rgb(var(--text-muted))] truncate">{{ $product->vendor->name }}</p>
                    @endif
                </div>
                @if ($inStock)
                    <span class="shrink-0 inline-flex items-center gap-1 text-caption font-medium text-[rgb(var(--success))]">
                        <span class="size-1.5 rounded-full bg-[rgb(var(--success))]"></span> In stock
                    </span>
                @endif
            </div>
        </div>
    </div>
</article>
