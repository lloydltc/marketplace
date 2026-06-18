@props([
    'context' => 'products',
    'query' => '',
])

{{-- Zero-results entry point into the RFQ flow (Phase 15). --}}
<div class="bg-white border border-dashed border-[#F0A820]/60 rounded-xl px-6 py-8 text-center max-w-lg mx-auto">
    <h3 class="text-base font-semibold text-neutral-900">Can't find what you're looking for?</h3>
    <p class="text-sm text-neutral-500 mt-1 mb-4">
        Post a request and verified sellers will send you quotes.
    </p>
    <a href="{{ route('rfq.create', array_filter(['q' => $query, 'for' => $context])) }}"
       class="inline-flex items-center gap-1 bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-5 py-2.5 rounded-lg text-sm transition-colors">
        Request it
        <span aria-hidden="true">→</span>
    </a>
</div>
