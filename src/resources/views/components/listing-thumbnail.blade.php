@props([
    'cover' => null,   // ProductImage|VehicleImage|null (the eager-loaded cover image)
    'alt'   => '',
    'type'  => 'vehicle', // 'vehicle' | 'product' — chooses the branded fallback
])

{{--
    D1: single source for listing thumbnails across landing, catalogue, search,
    dashboard and admin. Renders the real (processed) image with a responsive
    srcset + lazy-load; the branded icon shows ONLY when a listing genuinely has
    no image — never as a mask for a missing URL.
    The caller provides the sized container (aspect ratio + bg).
--}}
@if ($cover)
    <img src="{{ $cover->thumbUrl() }}"
         srcset="{{ $cover->thumbUrl() }} 300w, {{ $cover->mediumUrl() }} 800w"
         sizes="(max-width: 640px) 100vw, 25vw"
         alt="{{ $alt }}"
         loading="lazy" decoding="async"
         class="w-full h-full object-cover">
@elseif ($type === 'product')
    <span class="text-3xl text-neutral-300" aria-hidden="true">🔧</span>
@else
    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 17H5a2 2 0 01-2-2v-4l2-5h10l2 5v4a2 2 0 01-2 2h-3m-4 0h4m-4 0v-4h4v4" />
    </svg>
@endif
