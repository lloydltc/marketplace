@props([
    'images',                // Eloquent collection with ->mediumUrl() / ->thumbUrl()
    'alt' => '',
    'title' => '',
    'downloadUrl' => null,   // route to download-all (zip)
])

@if ($images->isNotEmpty())
    <div x-data="{
            i: 0,
            images: {{ Illuminate\Support\Js::from($images->map->mediumUrl()->values()) }},
            lightbox: false,
            shareUrl: '{{ url()->current() }}',
            shareText: {{ Illuminate\Support\Js::from($title . ' on SalmaDrive') }},
            next() { this.i = (this.i + 1) % this.images.length; },
            prev() { this.i = (this.i - 1 + this.images.length) % this.images.length; },
        }">
        <div class="rounded-xl overflow-hidden border border-base bg-surface-2 aspect-video relative">
            <img :src="images[i]" alt="{{ $alt }}" decoding="async"
                 @click="lightbox = true" class="w-full h-full object-cover cursor-zoom-in">
            <span class="absolute bottom-2 right-2 bg-[rgb(var(--bg-sidebar)/0.7)] text-white text-caption px-2 py-1 rounded-full tabular-nums backdrop-blur-sm">
                <span x-text="i + 1"></span> / {{ $images->count() }}
            </span>
        </div>

        @if ($images->count() > 1)
            <div class="flex gap-2 overflow-x-auto sd-rail pb-1 mt-2">
                @foreach ($images as $idx => $img)
                    <button type="button" @click="i = {{ $idx }}"
                            class="shrink-0 w-20 h-14 rounded-lg overflow-hidden border-2 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                            :class="i === {{ $idx }} ? 'border-brand' : 'border-transparent hover:border-strong'">
                        <img src="{{ $img->thumbUrl() }}" alt="" loading="lazy" decoding="async" class="w-full h-full object-cover">
                    </button>
                @endforeach
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-2 mt-3">
            <a :href="'https://wa.me/?text=' + encodeURIComponent(shareText + ' ' + shareUrl)" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1.5 text-body-sm font-medium border border-[rgb(var(--success))] text-[rgb(var(--success))] hover:bg-[rgb(var(--success)/0.1)] px-3 py-1.5 rounded-lg transition-colors">
                Share on WhatsApp
            </a>
            <button type="button" @click="navigator.clipboard.writeText(shareUrl); $el.textContent = 'Link copied'"
                    class="text-body-sm font-medium border border-strong text-[rgb(var(--text-muted))] hover:bg-surface-2 px-3 py-1.5 rounded-lg transition-colors">
                Copy link
            </button>
            @if ($downloadUrl)
                <a href="{{ $downloadUrl }}"
                   class="text-body-sm font-medium border border-strong text-[rgb(var(--text-muted))] hover:bg-surface-2 px-3 py-1.5 rounded-lg transition-colors">
                    Download all
                </a>
            @endif
        </div>

        {{-- Fullscreen lightbox --}}
        <div x-show="lightbox" x-cloak @keydown.escape.window="lightbox = false" x-trap.noscroll="lightbox"
             class="fixed inset-0 z-modal bg-black/90 flex items-center justify-center p-4">
            <button @click="lightbox = false" aria-label="Close" class="absolute top-4 right-4 text-white/80 hover:text-white text-3xl leading-none">&times;</button>
            <button @click="prev()" aria-label="Previous" class="absolute left-4 text-white/70 hover:text-white text-4xl px-3">&lsaquo;</button>
            <img :src="images[i]" alt="" class="max-h-[90vh] max-w-[90vw] object-contain rounded-lg">
            <button @click="next()" aria-label="Next" class="absolute right-4 text-white/70 hover:text-white text-4xl px-3">&rsaquo;</button>
        </div>
    </div>
@else
    <div class="bg-surface-2 rounded-xl aspect-video flex items-center justify-center text-[rgb(var(--text-muted))] border border-base">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-20 h-20 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="0.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 17H5a2 2 0 01-2-2v-4l2-5h10l2 5v4a2 2 0 01-2 2h-3m-4 0h4m-4 0v-4h4v4" />
        </svg>
    </div>
@endif
