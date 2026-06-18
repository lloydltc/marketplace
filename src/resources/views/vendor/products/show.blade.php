<x-layouts.app>
    <x-slot:title>{{ $product->title }}</x-slot:title>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('vendor.products.index') }}"
               class="text-sm text-neutral-500 hover:text-neutral-700">← Products</a>
            <span class="text-neutral-300">/</span>
            <span class="text-sm text-neutral-700 truncate">{{ $product->title }}</span>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-neutral-900 mb-4">{{ $product->title }}</h2>

                    @if ($product->sku)
                        <div class="mb-4 text-xs text-neutral-500 font-mono">SKU: {{ $product->sku }}</div>
                    @endif

                    <p class="text-sm text-neutral-700 whitespace-pre-line leading-relaxed">{{ $product->description }}</p>

                    <div class="mt-6 pt-5 border-t border-neutral-100 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-neutral-500">Price (ZWL)</span>
                            <div class="font-semibold tabular-nums">ZWL {{ number_format($product->price_zwl, 2) }}</div>
                        </div>
                        @if ($product->price_usd)
                        <div>
                            <span class="text-neutral-500">Price (USD)</span>
                            <div class="font-semibold tabular-nums">USD {{ number_format($product->price_usd, 2) }}</div>
                        </div>
                        @endif
                        <div>
                            <span class="text-neutral-500">Stock</span>
                            <div class="font-semibold tabular-nums">{{ $product->quantity }}</div>
                        </div>
                        <div>
                            <span class="text-neutral-500">Category</span>
                            <div class="font-medium">{{ $product->category?->name ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5">
                    @php
                        $badge = match($product->status) {
                            'active'   => 'bg-green-100 text-green-700',
                            'pending'  => 'bg-amber-100 text-amber-700',
                            'rejected' => 'bg-red-100 text-red-700',
                            default    => 'bg-neutral-100 text-neutral-600',
                        };
                    @endphp
                    <div class="text-xs text-neutral-500 uppercase tracking-wide mb-2">Status</div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $badge }}">
                        {{ ucfirst($product->status) }}
                    </span>

                    @if ($product->isPending())
                        <p class="text-xs text-neutral-500 mt-3">Your product is awaiting admin review.</p>
                    @elseif ($product->isRejected())
                        <p class="text-xs text-red-600 mt-3">This product was rejected. Edit and resubmit to request review again.</p>
                    @elseif ($product->isActive())
                        <p class="text-xs text-green-600 mt-3">This product is live and visible to customers.</p>
                    @endif
                </div>

                <div class="flex flex-col gap-2">
                    @can('update', $product)
                        <a href="{{ route('vendor.products.edit', $product) }}"
                           class="w-full text-center bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                            Edit Product
                        </a>
                    @endcan

                    @can('delete', $product)
                        <form method="POST" action="{{ route('vendor.products.destroy', $product) }}"
                              onsubmit="return confirm('Delete this product? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="w-full text-sm text-red-500 hover:text-red-700 py-2">
                                Delete Product
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
