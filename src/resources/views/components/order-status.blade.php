@props(['status'])

@php
    $tint = [
        'success' => 'bg-[rgb(var(--success)/0.15)] text-[rgb(var(--success))]',
        'warning' => 'bg-[rgb(var(--warning)/0.15)] text-[rgb(var(--warning))]',
        'info'    => 'bg-[rgb(var(--info)/0.15)] text-[rgb(var(--info))]',
        'danger'  => 'bg-[rgb(var(--danger)/0.15)] text-[rgb(var(--danger))]',
        'neutral' => 'bg-surface-2 text-muted',
    ];

    [$label, $key] = match ($status) {
        'pending_payment'  => ['Awaiting payment', 'neutral'],
        'cod_pending'      => ['Cash on delivery', 'warning'],
        'paid'             => ['Paid', 'success'],
        'processing'       => ['Processing', 'info'],
        'awaiting_pickup'  => ['Awaiting pickup', 'info'],
        'out_for_delivery' => ['Out for delivery', 'info'],
        'vendor_shipping'  => ['Shipping', 'info'],
        'delivered'        => ['Delivered', 'success'],
        'completed'        => ['Completed', 'success'],
        'cancelled'        => ['Cancelled', 'danger'],
        'refunded'         => ['Refunded', 'neutral'],
        'failed'           => ['Payment failed', 'danger'],
        default            => [ucfirst(str_replace('_', ' ', $status)), 'neutral'],
    };
@endphp

<span {{ $attributes->class("inline-flex items-center px-2 py-0.5 rounded-full text-caption font-medium {$tint[$key]}") }}>{{ $label }}</span>
