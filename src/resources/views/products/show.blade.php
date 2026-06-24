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

        <nav class="flex items-center gap-2 text-sm mb-6 text-neutral-500">
            <a href="{{ route('products.index') }}" class="hover:text-neutral-700">Products</a>
            <span>›</span>
            <span class="text-neutral-400">{{ $product->category?->name }}</span>
            <span>›</span>
            <span class="text-neutral-700 truncate">{{ $product->title }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">

            {{-- Product image placeholder --}}
            <div class="lg:col-span-2">
                <div class="bg-neutral-100 rounded-xl aspect-square flex items-center justify-center text-6xl text-neutral-300">
                    🔧
                </div>
            </div>

            {{-- Product details --}}
            <div class="lg:col-span-3">
                <div class="text-xs text-neutral-400 mb-2 uppercase tracking-wide">
                    {{ $product->category?->name }}
                </div>

                <h1 class="text-2xl font-semibold text-neutral-900 mb-4">{{ $product->title }}</h1>

                <div class="mb-6">
                    <div class="flex items-baseline gap-4">
                        <span class="text-3xl font-bold text-neutral-900 tabular-nums">
                            {{ $product->primaryPrice() }}
                        </span>
                        <span class="text-lg text-neutral-500 tabular-nums">
                            ≈ {{ $product->convertedZwl() }}
                        </span>
                    </div>
                    @if ($product->rateLabel())
                        <p class="text-xs text-neutral-400 mt-1">{{ $product->rateLabel() }}</p>
                    @endif
                </div>

                @inject('nav', 'App\Support\Navigation')
                @if ($product->isInStock())
                    <div class="text-sm text-[#2EBD7A] font-medium mb-4">
                        ✓ {{ $product->quantity }} in stock
                    </div>

                    @if (session('status'))
                        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-2">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{-- Buy CTA only for shoppers (customers + guests). A seller is not
                         a customer, so sellers/admins see no buyer surface (P1/G2). --}}
                    @if ($nav->canShop(auth()->user()))
                        <form method="POST" action="{{ route('cart.add') }}" class="flex items-center gap-3 mb-6">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="number" name="quantity" value="1" min="1" max="{{ min(99, $product->quantity) }}"
                                   class="w-20 border border-neutral-300 rounded-lg px-3 py-2.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                            <button type="submit"
                                    class="flex-1 bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold py-2.5 rounded-lg text-sm transition-colors">
                                Add to cart
                            </button>
                        </form>
                    @else
                        <div class="mb-6 text-sm text-neutral-500 bg-neutral-50 border border-neutral-200 rounded-lg px-4 py-3">
                            Purchasing is available to customer accounts.
                            <a href="{{ route('login') }}" class="text-[#3DB8E8] hover:underline">Sign in as a customer</a> to buy.
                        </div>
                    @endif
                @else
                    <div class="text-sm text-red-500 font-medium mb-6">Out of stock</div>
                @endif

                @if ($product->sku)
                    <div class="text-xs text-neutral-400 font-mono mb-6">SKU: {{ $product->sku }}</div>
                @endif

                <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-4 mb-6">
                    <div class="text-xs text-neutral-500 uppercase tracking-wide mb-2">Sold by</div>
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-neutral-800">{{ $product->vendor?->name }}</span>
                        <x-unverified-badge :verified="$product->vendor?->isApproved() ?? true" />
                    </div>
                    @unless ($product->vendor?->isApproved() ?? true)
                        <p class="text-xs text-neutral-500 mt-1">This seller is being verified — purchasing opens once approved.</p>
                    @endunless
                </div>

                <div class="prose prose-sm max-w-none text-neutral-700">
                    {!! nl2br(e($product->description)) !!}
                </div>

                {{-- H11: report this listing --}}
                <div class="mt-4">
                    <x-report-listing :action="route('products.report', $product)" />
                </div>

                {{-- H10: which vehicles this part fits --}}
                @if ($product->fitments->isNotEmpty())
                    <div class="mt-6 bg-neutral-50 border border-neutral-200 rounded-xl p-4">
                        <div class="text-xs text-neutral-500 uppercase tracking-wide mb-2">Fits these vehicles</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($product->fitments as $fitment)
                                <span class="text-xs font-medium bg-white border border-neutral-200 text-neutral-700 px-2.5 py-1 rounded-full">{{ $fitment->label() }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- H10: compatible vehicles currently on sale (cross-sell) --}}
        @if ($compatibleVehicles->isNotEmpty())
            <div class="mt-12">
                <h2 class="text-xl font-semibold text-neutral-900 mb-5">Compatible vehicles for sale</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
                    @foreach ($compatibleVehicles as $vehicle)
                        <a href="{{ route('vehicles.show', $vehicle) }}"
                           class="group bg-white border border-neutral-200 rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">
                            <div class="aspect-video bg-neutral-100 flex items-center justify-center overflow-hidden">
                                <x-listing-thumbnail :cover="$vehicle->coverImage()" :alt="$vehicle->displayTitle()" type="vehicle" />
                            </div>
                            <div class="p-4 flex flex-col flex-1">
                                <h3 class="text-sm font-semibold text-neutral-900 group-hover:text-[#F0A820] transition-colors leading-snug mb-2">{{ $vehicle->displayTitle() }}</h3>
                                <div class="mt-auto text-base font-bold text-neutral-900 tabular-nums">{{ $vehicle->primaryPrice() }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
