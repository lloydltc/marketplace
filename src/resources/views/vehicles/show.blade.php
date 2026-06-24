<x-layouts.app>
    <x-slot:title>{{ $vehicle->displayTitle() }}</x-slot:title>
    <x-slot:metaDescription>{{ $vehicle->displayTitle() }} — {{ ucfirst($vehicle->condition) }}, {{ number_format($vehicle->mileage) }} km. Listed on SalmaDrive.</x-slot:metaDescription>
    <x-slot:head>
        <script type="application/ld+json">
            {!! json_encode([
                '@context'      => 'https://schema.org',
                '@type'         => 'Car',
                'name'          => $vehicle->displayTitle(),
                'brand'         => $vehicle->make?->name,
                'model'         => $vehicle->vehicleModel?->name,
                'vehicleModelDate' => (string) $vehicle->year,
                'mileageFromOdometer' => ['@type' => 'QuantitativeValue', 'value' => $vehicle->mileage, 'unitCode' => 'KMT'],
                'fuelType'      => $vehicle->fuel_type,
                'offers'        => [
                    '@type'         => 'Offer',
                    'price'         => (string) ($vehicle->price_usd ?? $vehicle->price_zwl),
                    'priceCurrency' => $vehicle->price_usd !== null ? 'USD' : 'ZWL',
                    'url'           => route('vehicles.show', $vehicle),
                ],
            ], JSON_UNESCAPED_SLASHES) !!}
        </script>
    </x-slot:head>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('vehicles.index') }}"
               class="text-sm text-neutral-500 hover:text-neutral-700">← Vehicles</a>
            <span class="text-neutral-300">/</span>
            <span class="text-sm text-neutral-700">{{ $vehicle->displayTitle() }}</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 space-y-6">

                {{-- Image gallery (H3: count badge, fullscreen lightbox, share, download all) --}}
                @php $images = $vehicle->images; @endphp
                @if ($images->isNotEmpty())
                    <div x-data="{
                            i: 0,
                            images: {{ Illuminate\Support\Js::from($images->map->mediumUrl()->values()) }},
                            lightbox: false,
                            shareUrl: '{{ url()->current() }}',
                            shareText: {{ Illuminate\Support\Js::from($vehicle->displayTitle() . ' on SalmaDrive') }},
                            next() { this.i = (this.i + 1) % this.images.length; },
                            prev() { this.i = (this.i - 1 + this.images.length) % this.images.length; },
                        }">
                        <div class="rounded-xl overflow-hidden border border-neutral-200 bg-neutral-100 aspect-video relative">
                            <img :src="images[i]" alt="{{ $vehicle->displayTitle() }}" decoding="async"
                                 @click="lightbox = true" class="w-full h-full object-cover cursor-zoom-in">
                            <span class="absolute bottom-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded-full tabular-nums">
                                <span x-text="i + 1"></span> / {{ $images->count() }}
                            </span>
                        </div>

                        @if ($images->count() > 1)
                            <div class="flex gap-2 overflow-x-auto pb-1 mt-2">
                                @foreach ($images as $idx => $img)
                                    <button type="button" @click="i = {{ $idx }}"
                                            class="shrink-0 w-20 h-14 rounded-lg overflow-hidden border-2 transition-colors focus:outline-none"
                                            :class="i === {{ $idx }} ? 'border-[#F0A820]' : 'border-transparent hover:border-neutral-300'">
                                        <img src="{{ $img->thumbUrl() }}" alt="" loading="lazy" decoding="async" class="w-full h-full object-cover">
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        {{-- Share + download --}}
                        <div class="flex flex-wrap items-center gap-2 mt-3">
                            <a :href="'https://wa.me/?text=' + encodeURIComponent(shareText + ' ' + shareUrl)" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1.5 text-sm font-medium border border-[#2EBD7A] text-[#2EBD7A] hover:bg-green-50 px-3 py-1.5 rounded-lg">
                                Share on WhatsApp
                            </a>
                            <button type="button" @click="navigator.clipboard.writeText(shareUrl); $el.textContent = 'Link copied'"
                                    class="text-sm font-medium border border-neutral-300 text-neutral-600 hover:bg-neutral-50 px-3 py-1.5 rounded-lg">
                                Copy link
                            </button>
                            <a href="{{ route('vehicles.images.download', $vehicle) }}"
                               class="text-sm font-medium border border-neutral-300 text-neutral-600 hover:bg-neutral-50 px-3 py-1.5 rounded-lg">
                                Download all
                            </a>
                        </div>

                        {{-- Fullscreen lightbox --}}
                        <div x-show="lightbox" x-cloak @keydown.escape.window="lightbox = false"
                             class="fixed inset-0 z-[60] bg-black/90 flex items-center justify-center p-4" style="display:none;">
                            <button @click="lightbox = false" class="absolute top-4 right-4 text-white/80 hover:text-white text-3xl leading-none">&times;</button>
                            <button @click="prev()" class="absolute left-4 text-white/70 hover:text-white text-4xl px-3">&lsaquo;</button>
                            <img :src="images[i]" alt="" class="max-h-[90vh] max-w-[90vw] object-contain rounded-lg">
                            <button @click="next()" class="absolute right-4 text-white/70 hover:text-white text-4xl px-3">&rsaquo;</button>
                        </div>
                    </div>
                @else
                    <div class="bg-neutral-100 rounded-xl aspect-video flex items-center justify-center text-neutral-300 border border-neutral-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-20 h-20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="0.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 17H5a2 2 0 01-2-2v-4l2-5h10l2 5v4a2 2 0 01-2 2h-3m-4 0h4m-4 0v-4h4v4" />
                        </svg>
                    </div>
                @endif

                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
                    <div class="flex items-start justify-between gap-3 mb-1">
                        <h1 class="text-2xl font-bold text-neutral-900">{{ $vehicle->displayTitle() }}</h1>
                        {{-- H7: add this listing to the compare set --}}
                        <x-compare-toggle :vehicle="$vehicle" class="shrink-0" />
                    </div>

                    {{-- H2: Zimbabwe-market badges --}}
                    @if ($vehicle->is_recent_import || $vehicle->duty_paid || $vehicle->steering)
                        <div class="flex flex-wrap gap-2 mb-3">
                            @if ($vehicle->is_recent_import)
                                <span class="text-xs font-semibold bg-[#3DB8E8]/15 text-[#1E7FA8] px-2 py-0.5 rounded-full">Recent import</span>
                            @endif
                            @if ($vehicle->duty_paid)
                                <span class="text-xs font-semibold bg-[#2EBD7A]/15 text-[#1B8F5A] px-2 py-0.5 rounded-full">Duty paid</span>
                            @endif
                            @if ($vehicle->steering)
                                <span class="text-xs font-medium bg-neutral-100 text-neutral-600 px-2 py-0.5 rounded-full uppercase">{{ $vehicle->steering }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-2 text-xs text-neutral-500 mb-6">
                        <span class="capitalize">{{ $vehicle->body_type }}</span>
                        <span>·</span>
                        <span class="uppercase">{{ $vehicle->transmission }}</span>
                        <span>·</span>
                        <span class="capitalize">{{ $vehicle->fuel_type }}</span>
                        <span>·</span>
                        <span class="capitalize">{{ $vehicle->condition }}</span>
                        <span>·</span>
                        <span class="tabular-nums">{{ number_format($vehicle->mileage) }} km</span>
                    </div>

                    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">Make</dt>
                            <dd class="font-medium text-neutral-900">{{ $vehicle->make?->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">Model</dt>
                            <dd class="font-medium text-neutral-900">{{ $vehicle->vehicleModel?->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">Year</dt>
                            <dd class="text-neutral-900">{{ $vehicle->year }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">Color</dt>
                            <dd class="text-neutral-900 capitalize">{{ $vehicle->color }}</dd>
                        </div>
                        @if ($vehicle->engine_cc)
                        <div>
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">Engine</dt>
                            <dd class="text-neutral-900">{{ number_format($vehicle->engine_cc) }} cc</dd>
                        </div>
                        @endif
                        @if ($vehicle->steering)
                        <div>
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">Steering</dt>
                            <dd class="text-neutral-900 uppercase">{{ $vehicle->steering }}</dd>
                        </div>
                        @endif
                        @if ($vehicle->ref_code)
                        <div>
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">Ref code</dt>
                            <dd class="text-neutral-900 font-mono text-xs">{{ $vehicle->ref_code }}</dd>
                        </div>
                        @endif
                        @if ($vehicle->vin)
                        <div>
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">VIN</dt>
                            <dd class="text-neutral-900 font-mono text-xs">{{ $vehicle->vin }}</dd>
                        </div>
                        @endif
                    </dl>

                    {{-- Features & specs (D4) --}}
                    @php $featureGroups = $vehicle->groupedFeatures(); @endphp
                    @if (! empty($featureGroups))
                        <div class="mt-6 pt-6 border-t border-neutral-100">
                            <h2 class="text-sm font-semibold text-neutral-700 mb-3">Features &amp; specs</h2>
                            <div class="space-y-4">
                                @foreach ($featureGroups as $group => $values)
                                    <div>
                                        <h3 class="text-xs font-semibold text-neutral-400 uppercase tracking-wide mb-2">{{ $group }}</h3>
                                        <dl class="grid grid-cols-2 gap-x-6 gap-y-1.5 text-sm">
                                            @foreach ($values as $fv)
                                                <div class="flex items-center justify-between gap-2 border-b border-neutral-50 py-1">
                                                    <dt class="text-neutral-500">{{ $fv->definition->name }}</dt>
                                                    <dd class="text-neutral-900 font-medium text-right">
                                                        @if ($fv->definition->type === 'boolean')
                                                            @if ((int) $fv->value === 1)
                                                                <span class="text-[#2EBD7A]">✓ Yes</span>
                                                            @else
                                                                <span class="text-neutral-400">No</span>
                                                            @endif
                                                        @else
                                                            {{ $fv->display() }}
                                                        @endif
                                                    </dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($vehicle->description)
                        <div class="mt-6 pt-6 border-t border-neutral-100">
                            <h2 class="text-sm font-semibold text-neutral-700 mb-3">Description</h2>
                            <p class="text-sm text-neutral-700 whitespace-pre-line leading-relaxed">{{ $vehicle->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="space-y-5">
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 sticky top-6">
                    <div class="mb-4">
                        <div class="text-2xl font-bold text-neutral-900 tabular-nums">
                            {{ $vehicle->primaryPrice() }}
                        </div>
                        @if ($vehicle->secondaryPrice())
                            <div class="text-sm text-neutral-500 tabular-nums mt-0.5">
                                {{ $vehicle->secondaryPrice() }}
                            </div>
                        @endif
                    </div>

                    <div class="border-t border-neutral-100 pt-4 space-y-2 text-sm">
                        @unless ($vehicle->ownerIsVerified())
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2">
                                <x-unverified-badge :verified="false" />
                                <span class="text-xs text-yellow-700 ml-1">This seller hasn't completed verification yet.</span>
                            </div>
                        @endunless
                        <div class="flex items-center justify-between">
                            <span class="text-neutral-500">Seller type</span>
                            @if ($vehicle->isListedByVendor())
                                <span class="text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full font-medium">Dealer</span>
                            @else
                                <span class="text-xs bg-purple-50 text-purple-700 px-2 py-0.5 rounded-full font-medium">Private</span>
                            @endif
                        </div>
                        @if ($vehicle->isListedByVendor())
                            <div class="flex items-center justify-between">
                                <span class="text-neutral-500">Dealership</span>
                                <span class="text-neutral-800 font-medium">{{ $vehicle->vendor?->name }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-neutral-500">Listed</span>
                            <span class="text-neutral-600">{{ $vehicle->created_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    {{-- Contact seller (D6): records a lead, then reveals details --}}
                    <div class="mt-5" x-data="{
                        url: '{{ route('vehicles.contact', $vehicle) }}',
                        loading: false, revealed: false, contact: {},
                        async reveal(kind = 'contact_reveal') {
                            this.loading = true;
                            try {
                                const res = await fetch(this.url, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                    body: JSON.stringify({ type: kind }),
                                });
                                const data = await res.json();
                                if (data.ok) { this.contact = data.contact; this.revealed = true; }
                            } finally { this.loading = false; }
                        },
                        wa() { return this.contact.phone ? 'https://wa.me/' + this.contact.phone.replace(/[^0-9]/g, '') : '#'; }
                    }">
                        <button x-show="!revealed" @click="reveal()" :disabled="loading"
                                class="block w-full text-center bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2.5 rounded-lg text-sm transition-colors disabled:opacity-60">
                            <span x-show="!loading">Show contact details</span>
                            <span x-show="loading" x-cloak>Loading…</span>
                        </button>

                        <div x-show="revealed" x-cloak class="space-y-2">
                            <p class="text-sm font-medium text-neutral-900" x-text="contact.name"></p>
                            <template x-if="contact.phone">
                                <div class="space-y-2">
                                    {{-- WhatsApp is the primary CTA (H4) --}}
                                    <a :href="wa()" @click="reveal('whatsapp_click')" target="_blank" rel="noopener"
                                       class="flex items-center justify-center gap-2 bg-[#2EBD7A] hover:bg-[#2EBD7A]/90 text-white font-semibold px-3 py-2.5 rounded-lg text-sm">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.82 11.82 0 018.413 3.488 11.82 11.82 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zM6.6 20.13c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 001.51 5.26l-.999 3.648 3.978-1.207z"/></svg>
                                        WhatsApp seller
                                    </a>
                                    <a :href="'tel:' + contact.phone" @click="reveal('call_click')"
                                       class="block text-center border border-neutral-300 text-neutral-700 hover:bg-neutral-50 font-semibold px-3 py-2 rounded-lg text-sm">Call</a>
                                </div>
                            </template>
                            <template x-if="contact.phone">
                                <p class="text-sm text-neutral-700 text-center" x-text="contact.phone"></p>
                            </template>
                            <template x-if="contact.email">
                                <a :href="'mailto:' + contact.email" class="block text-sm text-[#3DB8E8] hover:underline text-center" x-text="contact.email"></a>
                            </template>
                        </div>

                        <p class="text-[11px] text-neutral-400 mt-2 text-center leading-snug">
                            By contacting, you agree your details may be shared with the seller.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- H7: other listings this buyer has recently viewed --}}
    <x-vehicle-row title="Recently viewed" :vehicles="$recentlyViewed" />
</x-layouts.app>
