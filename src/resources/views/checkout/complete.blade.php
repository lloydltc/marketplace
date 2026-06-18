<x-layouts.app>
    <x-slot:title>Order placed</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="text-center mb-8">
            <div class="w-14 h-14 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-2xl font-semibold text-neutral-900">Thank you — your order is placed</h1>
            <p class="text-sm text-neutral-500 mt-1">
                {{ count($orders) }} {{ Str::plural('order', count($orders)) }} created. Complete payment for prepaid orders below.
            </p>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif
        @error('payment')
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $message }}</div>
        @enderror

        <div class="space-y-4">
            @foreach ($orders as $order)
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-mono text-sm text-neutral-700">{{ $order->order_number }}</span>
                        @php
                            $badge = match ($order->status) {
                                'paid'           => ['Paid', 'bg-green-50 text-green-700'],
                                'cod_pending'    => ['Cash on delivery', 'bg-amber-50 text-amber-700'],
                                'failed'         => ['Payment failed', 'bg-red-50 text-red-600'],
                                default          => ['Awaiting payment', 'bg-neutral-100 text-neutral-500'],
                            };
                        @endphp
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $badge[1] }}">{{ $badge[0] }}</span>
                    </div>

                    <ul class="text-sm text-neutral-600 space-y-1 mb-3">
                        @foreach ($order->items as $item)
                            <li class="flex justify-between">
                                <span>{{ $item->quantity }} × {{ $item->title }}</span>
                                <span class="tabular-nums">ZWL {{ number_format($item->line_total, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="border-t border-neutral-100 pt-3">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm text-neutral-500">
                                Total <span class="font-semibold text-neutral-900 tabular-nums">ZWL {{ number_format($order->total, 2) }}</span>
                                <span class="text-neutral-400">({{ $order->fulfilment_track === 'fbs' ? 'Fulfilled by Salma' : 'Vendor-fulfilled' }})</span>
                            </span>
                            @if ($order->isCod())
                                <span class="text-xs text-neutral-400">Pay cash on delivery</span>
                            @endif
                        </div>

                        @if ($order->isPrepaid() && $order->isAwaitingPayment())
                            <div x-data="{ method: 'ecocash' }" class="bg-neutral-50 border border-neutral-200 rounded-lg p-3 space-y-3">
                                <div class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Choose payment</div>

                                <div class="flex flex-wrap gap-2 text-sm">
                                    <label class="flex items-center gap-1.5"><input type="radio" name="m{{ $order->id }}" value="ecocash" x-model="method" class="text-[#F0A820] focus:ring-[#F0A820]"> EcoCash</label>
                                    <label class="flex items-center gap-1.5"><input type="radio" name="m{{ $order->id }}" value="innbucks" x-model="method" class="text-[#F0A820] focus:ring-[#F0A820]"> InnBucks</label>
                                    <label class="flex items-center gap-1.5"><input type="radio" name="m{{ $order->id }}" value="card" x-model="method" class="text-[#F0A820] focus:ring-[#F0A820]"> Card / bank</label>
                                </div>

                                {{-- EcoCash (seamless: phone + approve on device) --}}
                                <form x-show="method === 'ecocash'" method="POST" action="{{ route('payments.seamless', $order) }}" class="flex items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="method" value="ecocash">
                                    <input type="tel" name="phone" required placeholder="EcoCash number e.g. 0771234567"
                                           value="{{ $order->buyer_phone }}"
                                           class="flex-1 border border-neutral-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                                    <button type="submit" class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm whitespace-nowrap">Pay</button>
                                </form>

                                {{-- InnBucks (seamless: approve in app) --}}
                                <form x-show="method === 'innbucks'" method="POST" action="{{ route('payments.seamless', $order) }}" class="flex items-center justify-between">
                                    @csrf
                                    <input type="hidden" name="method" value="innbucks">
                                    <span class="text-xs text-neutral-500">Approve the request in your InnBucks app.</span>
                                    <button type="submit" class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm">Pay</button>
                                </form>

                                {{-- Card / bank (hosted redirect) --}}
                                <form x-show="method === 'card'" method="POST" action="{{ route('payments.initiate', $order) }}" class="flex items-center justify-between">
                                    @csrf
                                    <span class="text-xs text-neutral-500">You'll be taken to Pesepay's secure page.</span>
                                    <button type="submit" class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm">Continue</button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-center mt-8">
            <a href="{{ route('products.index') }}" class="text-sm text-[#3DB8E8] hover:underline">Continue shopping</a>
        </div>
    </div>
</x-layouts.app>
