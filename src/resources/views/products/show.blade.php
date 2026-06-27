<x-layouts.app>
    <x-slot:title>{{ $product->title }}</x-slot:title>
    <x-slot:metaDescription>{{ Str::limit(strip_tags($product->description), 155) }}</x-slot:metaDescription>
    <x-slot:head>
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type'    => 'Product',
                'name'     => $product->title,
                'description' => Str::limit(strip_tags($product->description), 300),
                'category' => $product->category?->name,
                'offers'   => [
                    '@type'         => 'Offer',
                    'price'         => (string) $product->price_usd,
                    'priceCurrency' => 'USD',
                    'availability'  => $product->isInStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'url'           => route('products.show', $product),
                ],
            ], JSON_UNESCAPED_SLASHES) !!}
        </script>
    </x-slot:head>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <x-breadcrumbs class="mb-6" :items="[
            ['label' => 'Parts', 'url' => route('products.index')],
            ['label' => $product->category?->name, 'url' => route('products.index', ['category' => $product->category_id])],
            ['label' => $product->title],
        ]" />

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">

            {{-- Product image --}}
            <div class="lg:col-span-2">
                <div class="bg-surface-2 border border-line rounded-xl aspect-square grid place-items-center overflow-hidden">
                    <x-listing-thumbnail :cover="$product->coverImage()" :alt="$product->title" type="product" />
                </div>
            </div>

            {{-- Product details --}}
            <div class="lg:col-span-3">
                <p class="text-overline uppercase text-muted mb-2">{{ $product->category?->name }}</p>
                <h1 class="text-h1 text-ink mb-4">{{ $product->title }}</h1>

                <div class="mb-6">
                    <div class="flex items-baseline gap-3 flex-wrap">
                        <x-price :value="$product->primaryPrice()" size="xl" />
                        <span class="text-body text-muted tabular-nums">≈ {{ $product->convertedZwl() }}</span>
                    </div>
                    @if ($product->rateLabel())
                        <p class="text-caption text-muted mt-1">{{ $product->rateLabel() }}</p>
                    @endif
                </div>

                @inject('nav', 'App\Support\Navigation')
                @if ($product->isInStock())
                    <p class="inline-flex items-center gap-1.5 text-body-sm font-medium text-[rgb(var(--success))] mb-4">
                        <span class="size-1.5 rounded-full bg-[rgb(var(--success))]"></span> {{ $product->quantity }} in stock
                    </p>

                    @if (session('status'))
                        <div class="mb-4 bg-[rgb(var(--success)/0.12)] border border-[rgb(var(--success)/0.3)] text-[rgb(var(--success))] text-body-sm rounded-lg px-4 py-2" role="status">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{-- Buy CTA only for shoppers (customers + guests). --}}
                    @if ($nav->canShop(auth()->user()))
                        <form method="POST" action="{{ route('cart.add') }}" class="flex items-center gap-3 mb-6">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="number" name="quantity" value="1" min="1" max="{{ min(99, $product->quantity) }}"
                                   class="w-20 h-11 text-center rounded-md bg-surface text-ink border border-strong focus-visible:outline-none focus:ring-2 focus:ring-brand focus:border-brand text-body-sm">
                            <x-button type="submit" size="lg" class="flex-1">Add to cart</x-button>
                        </form>
                    @else
                        <div class="mb-6 text-body-sm text-muted bg-surface-2 border border-line rounded-lg px-4 py-3">
                            Purchasing is available to customer accounts.
                            <a href="{{ route('login') }}" class="text-[rgb(var(--info))] hover:underline">Sign in as a customer</a> to buy.
                        </div>
                    @endif
                @else
                    <div class="text-body-sm text-[rgb(var(--danger))] font-medium mb-6">Out of stock</div>
                @endif

                @if ($product->sku)
                    <div class="text-caption text-muted font-mono mb-6">SKU: {{ $product->sku }}</div>
                @endif

                <x-card padding="sm" elevation="e0" class="bg-surface-2 mb-6">
                    <div class="text-overline uppercase text-muted mb-2">Sold by</div>
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-ink">{{ $product->vendor?->name }}</span>
                        <x-unverified-badge :verified="$product->vendor?->isApproved() ?? true" />
                    </div>
                    @unless ($product->vendor?->isApproved() ?? true)
                        <p class="text-caption text-muted mt-1">This seller is being verified — purchasing opens once approved.</p>
                    @endunless
                </x-card>

                <div class="text-body-sm text-[rgb(var(--text))] leading-relaxed">
                    {!! nl2br(e($product->description)) !!}
                </div>

                <div class="mt-4">
                    <x-report-listing :action="route('products.report', $product)" />
                </div>

                {{-- H10: which vehicles this part fits --}}
                @if ($product->fitments->isNotEmpty())
                    <x-card padding="sm" elevation="e0" class="bg-surface-2 mt-6">
                        <div class="text-overline uppercase text-muted mb-2">Fits these vehicles</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($product->fitments as $fitment)
                                <span class="text-caption font-medium bg-surface border border-line text-[rgb(var(--text))] px-2.5 py-1 rounded-full">{{ $fitment->label() }}</span>
                            @endforeach
                        </div>
                    </x-card>
                @endif
            </div>
        </div>

        {{-- H10: compatible vehicles currently on sale (cross-sell) --}}
        @if ($compatibleVehicles->isNotEmpty())
            <div class="mt-12">
                <h2 class="text-h2 text-ink mb-5">Compatible vehicles for sale</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach ($compatibleVehicles as $vehicle)
                        <x-vehicle-card :vehicle="$vehicle" :compare="false" />
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
