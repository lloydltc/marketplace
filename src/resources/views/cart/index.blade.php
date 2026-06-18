<x-layouts.app>
    <x-slot:title>Your Cart</x-slot:title>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-neutral-900">Your Cart</h1>
            @unless (empty($groups))
                <form method="POST" action="{{ route('cart.clear') }}"
                      onsubmit="return confirm('Empty your cart?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm text-neutral-500 hover:text-red-600">Clear cart</button>
                </form>
            @endunless
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif
        @error('cart')
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $message }}</div>
        @enderror

        @if (empty($groups))
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm py-16 text-center">
                <p class="text-neutral-500 text-sm">Your cart is empty.</p>
                <a href="{{ route('products.index') }}" class="mt-3 inline-block text-sm text-[#3DB8E8] hover:underline">Browse products</a>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Order groups --}}
                <div class="lg:col-span-2 space-y-5">
                    @foreach ($groups as $group)
                        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
                            {{-- Group header --}}
                            <div class="px-5 py-3 border-b border-neutral-100 bg-neutral-50 flex items-center justify-between gap-3 flex-wrap">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-neutral-800">{{ $group->vendorName }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-600">{{ $group->deliveryLabel }}</span>
                                </div>
                                @if ($group->codAvailable)
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-green-50 text-green-700">Cash on delivery available</span>
                                @else
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-500">Prepaid only</span>
                                @endif
                            </div>

                            {{-- Lines --}}
                            <div class="divide-y divide-neutral-100">
                                @foreach ($group->lines as $line)
                                    <div class="px-5 py-4 flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-lg bg-neutral-100 flex items-center justify-center text-xl text-neutral-300 shrink-0">🔧</div>
                                        <div class="flex-1 min-w-0">
                                            <a href="{{ route('products.show', $line->product) }}"
                                               class="text-sm font-medium text-neutral-900 hover:text-[#F0A820] line-clamp-1">
                                                {{ $line->product->title }}
                                            </a>
                                            <div class="text-xs text-neutral-400">ZWL {{ number_format($line->product->price_zwl, 2) }} each</div>
                                        </div>

                                        <form method="POST" action="{{ route('cart.update', $line->product) }}" class="flex items-center gap-2">
                                            @csrf @method('PATCH')
                                            <input type="number" name="quantity" value="{{ $line->quantity }}" min="0" max="99"
                                                   class="w-16 border border-neutral-200 rounded-lg px-2 py-1 text-sm text-center focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                                            <button type="submit" class="text-xs text-[#3DB8E8] hover:underline">Update</button>
                                        </form>

                                        <div class="w-28 text-right text-sm font-semibold text-neutral-900 tabular-nums">
                                            ZWL {{ number_format($line->lineTotal(), 2) }}
                                        </div>

                                        <form method="POST" action="{{ route('cart.remove', $line->product) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-neutral-300 hover:text-red-500" title="Remove">✕</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Group footer --}}
                            <div class="px-5 py-3 bg-neutral-50 border-t border-neutral-100 text-sm flex items-center justify-between">
                                <span class="text-neutral-500">
                                    Delivery:
                                    @if ($group->deliveryFee === null)
                                        <span class="text-neutral-700">arranged with vendor</span>
                                    @else
                                        <span class="text-neutral-700 tabular-nums">ZWL {{ number_format($group->deliveryFee, 2) }}</span>
                                    @endif
                                </span>
                                <span class="font-semibold text-neutral-900 tabular-nums">
                                    Subtotal: ZWL {{ number_format($group->subtotal(), 2) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Summary --}}
                <div class="lg:col-span-1">
                    <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 sticky top-6">
                        <h2 class="text-base font-semibold text-neutral-900 mb-4">Order summary</h2>

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-neutral-500">Items ({{ $count }})</span>
                                <span class="text-neutral-800 tabular-nums">ZWL {{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-neutral-500">Delivery (est.)</span>
                                <span class="text-neutral-800 tabular-nums">ZWL {{ number_format($total - $subtotal, 2) }}</span>
                            </div>
                            {{-- Coupons: deferred to Phase 20 --}}
                            <div class="border-t border-neutral-100 pt-3 flex justify-between font-semibold">
                                <span class="text-neutral-900">Total</span>
                                <span class="text-neutral-900 tabular-nums">ZWL {{ number_format($total, 2) }}</span>
                            </div>
                        </div>

                        <p class="text-xs text-neutral-400 mt-2">
                            {{ count($groups) }} {{ Str::plural('order', count($groups)) }} will be created — one per vendor and fulfilment track.
                        </p>

                        <a href="{{ route('checkout.show') }}"
                           class="mt-5 block w-full text-center bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold py-2.5 rounded-lg text-sm transition-colors">
                            Proceed to checkout
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
