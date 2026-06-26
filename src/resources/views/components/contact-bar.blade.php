@props([
    'contactUrl',            // route('vehicles.contact', $vehicle) — records a lead, returns contact
    'price' => null,         // formatted, for the mobile sticky bar
])

<div x-data="contactReveal('{{ $contactUrl }}', '{{ csrf_token() }}')">
    {{-- Inline (desktop sidebar) --}}
    <button x-show="!revealed" @click="reveal()" :disabled="loading"
            class="block w-full text-center bg-brand hover:bg-brand-hover text-on-brand font-semibold px-4 py-2.5 rounded-lg text-body-sm transition-colors disabled:opacity-60">
        <span x-show="!loading">Show contact details</span>
        <span x-show="loading" x-cloak>Loading…</span>
    </button>

    <div x-show="revealed" x-cloak class="space-y-2">
        <p class="text-body-sm font-medium text-strong" x-text="contact.name"></p>
        <template x-if="contact.phone">
            <div class="space-y-2">
                <a :href="wa()" @click="reveal('whatsapp_click')" target="_blank" rel="noopener"
                   class="flex items-center justify-center gap-2 bg-[rgb(var(--success))] hover:opacity-90 text-white font-semibold px-3 py-2.5 rounded-lg text-body-sm transition-opacity">
                    <svg class="size-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.82 11.82 0 018.413 3.488 11.82 11.82 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zM6.6 20.13c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 001.51 5.26l-.999 3.648 3.978-1.207z"/></svg>
                    WhatsApp seller
                </a>
                <a :href="'tel:' + contact.phone" @click="reveal('call_click')"
                   class="block text-center border border-strong text-[rgb(var(--text))] hover:bg-surface-2 font-semibold px-3 py-2 rounded-lg text-body-sm transition-colors">Call</a>
            </div>
        </template>
        <template x-if="contact.phone"><p class="text-body-sm text-[rgb(var(--text))] text-center" x-text="contact.phone"></p></template>
        <template x-if="contact.email"><a :href="'mailto:' + contact.email" class="block text-body-sm text-[rgb(var(--info))] hover:underline text-center" x-text="contact.email"></a></template>
    </div>

    <p class="text-caption text-[rgb(var(--text-muted))] mt-2 text-center leading-snug">
        By contacting, you agree your details may be shared with the seller.
    </p>

    {{-- Mobile sticky bottom bar (shares this Alpine scope via teleport) --}}
    <template x-teleport="body">
        <div class="lg:hidden fixed inset-x-0 bottom-0 z-sticky bg-surface border-t border-base shadow-e3 px-4 py-3 flex items-center gap-3">
            @if ($price)<div class="shrink-0"><x-price :value="$price" /></div>@endif
            <button x-show="!revealed" @click="reveal()" :disabled="loading"
                    class="flex-1 inline-flex items-center justify-center bg-brand hover:bg-brand-hover text-on-brand font-semibold px-4 h-11 rounded-lg text-body-sm transition-colors disabled:opacity-60">
                Show contact
            </button>
            <a x-show="revealed" x-cloak :href="wa()" @click="reveal('whatsapp_click')" target="_blank" rel="noopener"
               class="flex-1 inline-flex items-center justify-center gap-2 bg-[rgb(var(--success))] text-white font-semibold px-4 h-11 rounded-lg text-body-sm">
                WhatsApp seller
            </a>
        </div>
    </template>
</div>

@once
    <script>
        function contactReveal(url, token) {
            return {
                url, token, loading: false, revealed: false, contact: {},
                async reveal(kind = 'contact_reveal') {
                    this.loading = true;
                    try {
                        const res = await fetch(this.url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.token },
                            body: JSON.stringify({ type: kind }),
                        });
                        const data = await res.json();
                        if (data.ok) { this.contact = data.contact; this.revealed = true; }
                    } finally { this.loading = false; }
                },
                wa() { return this.contact.phone ? 'https://wa.me/' + this.contact.phone.replace(/[^0-9]/g, '') : '#'; },
            };
        }
    </script>
@endonce
