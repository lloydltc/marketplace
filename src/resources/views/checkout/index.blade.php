<x-layouts.app>
    <x-slot:title>Checkout</x-slot:title>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-2xl font-semibold text-neutral-900 mb-6">Checkout</h1>

        @error('checkout')
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('checkout.store') }}" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            @csrf

            <div class="lg:col-span-2 space-y-6">
                {{-- Contact & shipping --}}
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5">
                    <h2 class="text-base font-semibold text-neutral-900 mb-4">Contact & delivery address</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-neutral-700">Full name</label>
                            <input name="full_name" value="{{ old('full_name', $prefill['full_name']) }}" required
                                   class="block w-full border rounded-lg px-3 py-2.5 text-sm @error('full_name') border-red-500 @else border-neutral-300 @enderror">
                            @error('full_name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-neutral-700">Email</label>
                            <input type="email" name="email" value="{{ old('email', $prefill['email']) }}" required
                                   class="block w-full border rounded-lg px-3 py-2.5 text-sm @error('email') border-red-500 @else border-neutral-300 @enderror">
                            @error('email') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-neutral-700">Phone</label>
                            <input name="phone" value="{{ old('phone') }}" required
                                   class="block w-full border rounded-lg px-3 py-2.5 text-sm @error('phone') border-red-500 @else border-neutral-300 @enderror">
                            @error('phone') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-neutral-700">City / town</label>
                            <input name="city" value="{{ old('city') }}" required
                                   class="block w-full border rounded-lg px-3 py-2.5 text-sm @error('city') border-red-500 @else border-neutral-300 @enderror">
                            @error('city') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1 sm:col-span-2">
                            <label class="block text-sm font-medium text-neutral-700">Delivery address</label>
                            <input name="address" value="{{ old('address') }}" required
                                   class="block w-full border rounded-lg px-3 py-2.5 text-sm @error('address') border-red-500 @else border-neutral-300 @enderror">
                            @error('address') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Per-group fulfilment + payment --}}
                @foreach ($groups as $group)
                    @php $groupChoices = $choices[$group->key()]; $codAnywhere = collect($groupChoices)->contains(fn ($o) => $o['cod']); @endphp
                    <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-base font-semibold text-neutral-900">{{ $group->vendorName }}</h2>
                            <span class="text-sm text-neutral-500 tabular-nums">ZWL {{ number_format($group->subtotal(), 2) }}</span>
                        </div>

                        <ul class="text-sm text-neutral-500 mb-4 space-y-0.5">
                            @foreach ($group->lines as $line)
                                <li>{{ $line->quantity }} × {{ $line->product->title }}</li>
                            @endforeach
                        </ul>

                        {{-- Fulfilment --}}
                        <div class="mb-4">
                            <div class="text-xs font-medium text-neutral-500 uppercase tracking-wide mb-2">Delivery method</div>
                            <div class="space-y-2">
                                @foreach ($groupChoices as $track => $opt)
                                    <label class="flex items-center gap-2 text-sm text-neutral-700">
                                        <input type="radio" name="fulfilment[{{ $group->key() }}]" value="{{ $track }}"
                                               @checked($loop->first) required
                                               class="text-[#F0A820] focus:ring-[#F0A820]">
                                        <span>
                                            {{ $track === 'fbs' ? 'Fulfilled by Salma' : 'Vendor delivery' }}
                                            @if ($opt['deliveryFee'] !== null)
                                                — <span class="tabular-nums">ZWL {{ number_format($opt['deliveryFee'], 2) }}</span>
                                            @else
                                                — arranged with vendor
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Payment --}}
                        <div>
                            <div class="text-xs font-medium text-neutral-500 uppercase tracking-wide mb-2">Payment</div>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-sm text-neutral-700">
                                    <input type="radio" name="payment[{{ $group->key() }}]" value="prepaid" checked
                                           class="text-[#F0A820] focus:ring-[#F0A820]">
                                    Prepaid (card / mobile money)
                                </label>
                                @if ($codAnywhere)
                                    <label class="flex items-center gap-2 text-sm text-neutral-700">
                                        <input type="radio" name="payment[{{ $group->key() }}]" value="cod"
                                               class="text-[#F0A820] focus:ring-[#F0A820]">
                                        Cash on delivery <span class="text-xs text-neutral-400">(where eligible)</span>
                                    </label>
                                @else
                                    <p class="text-xs text-neutral-400">Cash on delivery isn't available for this order.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Summary --}}
            <div class="lg:col-span-1">
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 sticky top-6">
                    <h2 class="text-base font-semibold text-neutral-900 mb-4">Summary</h2>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-neutral-500">Items</span>
                        <span class="text-neutral-800 tabular-nums">ZWL {{ number_format($subtotal, 2) }}</span>
                    </div>
                    <p class="text-xs text-neutral-400 mb-4">Delivery is calculated from your choices on the next step.</p>
                    <button type="submit"
                            class="w-full bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold py-2.5 rounded-lg text-sm transition-colors">
                        Continue to payment
                    </button>
                    <a href="{{ route('cart.index') }}" class="block text-center text-sm text-neutral-500 hover:text-neutral-700 mt-3">← Back to cart</a>
                </div>
            </div>
        </form>
    </div>
</x-layouts.app>
