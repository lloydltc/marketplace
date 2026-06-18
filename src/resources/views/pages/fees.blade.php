<x-content-page title="Fees" metaDescription="SalmaDrive's transparent fee schedule — commission, delivery, RFQ, and listing promotion charges.">
    <p>These are SalmaDrive's current fees, shown straight from our live configuration. They may be tuned over time; this page always reflects what's actually charged.</p>

    <div class="not-prose overflow-hidden rounded-xl border border-neutral-200">
        <table class="w-full text-sm">
            <tbody class="divide-y divide-neutral-100">
                <tr><td class="px-4 py-3 text-neutral-600">Platform commission (parts)</td><td class="px-4 py-3 text-right font-medium tabular-nums">{{ rtrim(rtrim(number_format($fees['commission_rate'], 2), '0'), '.') }}%</td></tr>
                <tr><td class="px-4 py-3 text-neutral-600">Delivery fee (Fulfilled by Salma)</td><td class="px-4 py-3 text-right font-medium tabular-nums">from ${{ number_format($fees['delivery_fbs'], 2) }}</td></tr>
                <tr><td class="px-4 py-3 text-neutral-600">Concierge service</td><td class="px-4 py-3 text-right font-medium tabular-nums">max(${{ number_format($fees['concierge_min'], 2) }}, {{ rtrim(rtrim(number_format($fees['concierge_percent'], 2), '0'), '.') }}%)</td></tr>
                <tr><td class="px-4 py-3 text-neutral-600">Request a part (RFQ)</td><td class="px-4 py-3 text-right font-medium tabular-nums">{{ $fees['rfq_free_quota'] }} free / month, then ${{ number_format($fees['rfq_overage'], 2) }} each</td></tr>
                <tr><td class="px-4 py-3 text-neutral-600">Featured vehicle listing</td><td class="px-4 py-3 text-right font-medium tabular-nums">${{ number_format($fees['featured_vehicle_fee'], 2) }}</td></tr>
                <tr><td class="px-4 py-3 text-neutral-600">Listing bump</td><td class="px-4 py-3 text-right font-medium tabular-nums">${{ number_format($fees['listing_bump_fee'], 2) }}</td></tr>
            </tbody>
        </table>
    </div>

    <p class="text-sm text-neutral-500">Basic vehicle and parts listings are free. Vehicle listings are lead-gen — buyers contact sellers directly; there is no online checkout for vehicles.</p>
</x-content-page>
