@props(['verified' => true])

@unless ($verified)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700']) }}>
        Unverified seller
    </span>
@endunless
