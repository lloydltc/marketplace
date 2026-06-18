<x-layouts.app>
    <x-slot:title>Payment status</x-slot:title>

    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
        @php
            $paid = $order->isPaid();
            $failed = $order->status === 'failed';
        @endphp

        <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-5
                    {{ $paid ? 'bg-green-50' : ($failed ? 'bg-red-50' : 'bg-amber-50') }}">
            @if ($paid)
                <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            @elseif ($failed)
                <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            @else
                <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"/></svg>
            @endif
        </div>

        <h1 class="text-2xl font-semibold text-neutral-900 mb-1">
            @if ($paid) Payment received
            @elseif ($failed) Payment failed
            @else Payment pending
            @endif
        </h1>
        <p class="text-sm text-neutral-500 mb-2">Order <span class="font-mono">{{ $order->order_number }}</span></p>
        <p class="text-sm text-neutral-500">
            @if ($paid)
                We've confirmed your payment with the gateway. A receipt is on its way.
            @elseif ($failed)
                Your payment didn't go through. You can try again from your order.
            @else
                Approve the request on your phone (EcoCash / InnBucks), then re-check below.
                This page reflects the gateway-verified status — not the browser redirect.
            @endif
        </p>

        <div class="mt-8 flex items-center justify-center gap-4">
            @unless ($paid || $failed)
                <a href="{{ route('payments.return', $order) }}"
                   class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm">
                    Check payment status
                </a>
            @endunless
            <a href="{{ route('checkout.complete') }}" class="text-sm text-[#3DB8E8] hover:underline">Back to orders</a>
            <a href="{{ route('products.index') }}" class="text-sm text-neutral-500 hover:text-neutral-700">Continue shopping</a>
        </div>
    </div>
</x-layouts.app>
