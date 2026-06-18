@props(['status'])

@php
    [$label, $classes] = match ($status) {
        'pending_payment'  => ['Awaiting payment', 'bg-neutral-100 text-neutral-600'],
        'cod_pending'      => ['Cash on delivery', 'bg-amber-50 text-amber-700'],
        'paid'             => ['Paid', 'bg-green-50 text-green-700'],
        'processing'       => ['Processing', 'bg-blue-50 text-blue-700'],
        'awaiting_pickup'  => ['Awaiting pickup', 'bg-blue-50 text-blue-700'],
        'out_for_delivery' => ['Out for delivery', 'bg-indigo-50 text-indigo-700'],
        'vendor_shipping'  => ['Shipping', 'bg-indigo-50 text-indigo-700'],
        'delivered'        => ['Delivered', 'bg-teal-50 text-teal-700'],
        'completed'        => ['Completed', 'bg-green-100 text-green-800'],
        'cancelled'        => ['Cancelled', 'bg-red-50 text-red-600'],
        'refunded'         => ['Refunded', 'bg-purple-50 text-purple-700'],
        'failed'           => ['Payment failed', 'bg-red-50 text-red-600'],
        default            => [ucfirst(str_replace('_', ' ', $status)), 'bg-neutral-100 text-neutral-600'],
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {$classes}"]) }}>{{ $label }}</span>
