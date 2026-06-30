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

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 pb-24 lg:pb-10">

        <x-breadcrumbs class="mb-6" :items="[
            ['label' => 'Vehicles', 'url' => route('vehicles.index')],
            ['label' => $vehicle->displayTitle()],
        ]" />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 space-y-6">

                {{-- Gallery (H3) --}}
                <x-gallery :images="$vehicle->images" :alt="$vehicle->displayTitle()"
                           :title="$vehicle->displayTitle()" :download-url="route('vehicles.images.download', $vehicle)" />

                <x-card padding="lg">
                    <div class="flex items-start justify-between gap-3 mb-1">
                        <h1 class="text-h1 text-ink">{{ $vehicle->displayTitle() }}</h1>
                        <x-compare-toggle :vehicle="$vehicle" class="shrink-0" />
                    </div>

                    @if ($vehicle->isListedByVendor() && $vehicle->vendor?->isApproved())
                        <a href="{{ $vehicle->vendor->storefrontUrl() }}"
                           class="inline-flex items-center gap-1 text-body-sm text-[rgb(var(--info))] hover:underline mb-1">
                            Sold by {{ $vehicle->vendor->name }} →
                        </a>
                    @endif

                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <x-expiry-badge :vehicle="$vehicle" />
                        {{-- HR3: vehicle history report --}}
                        <a href="{{ route('history.preview', $vehicle) }}"
                           class="inline-flex items-center gap-1 text-caption font-semibold bg-[rgb(var(--info)/0.15)] text-[rgb(var(--info))] px-2 py-0.5 rounded-full hover:underline">
                            📄 Vehicle history available
                        </a>
                    </div>

                    {{-- H2: Zimbabwe-market badges --}}
                    @if ($vehicle->is_recent_import || $vehicle->duty_paid || $vehicle->steering)
                        <div class="flex flex-wrap gap-2 mb-4">
                            @if ($vehicle->is_recent_import)<x-badge variant="recent-import" />@endif
                            @if ($vehicle->duty_paid)<x-badge variant="duty-paid" />@endif
                            @if ($vehicle->steering)<x-badge variant="neutral" class="uppercase">{{ $vehicle->steering }}</x-badge>@endif
                        </div>
                    @endif

                    {{-- spec chips --}}
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-caption text-[rgb(var(--text-muted))] mb-6">
                        @foreach (array_filter([ucfirst($vehicle->body_type), ucfirst($vehicle->transmission), ucfirst($vehicle->fuel_type), ucfirst($vehicle->condition), number_format($vehicle->mileage) . ' km']) as $chip)
                            @if (! $loop->first)<span class="size-1 rounded-full bg-[rgb(var(--border-strong))]"></span>@endif
                            <span>{{ $chip }}</span>
                        @endforeach
                    </div>

                    {{-- key specs --}}
                    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-4">
                        @php
                            $specs = array_filter([
                                'Make' => $vehicle->make?->name,
                                'Model' => $vehicle->vehicleModel?->name,
                                'Year' => $vehicle->year,
                                'Color' => $vehicle->color ? ucfirst($vehicle->color) : null,
                                'Engine' => $vehicle->engine_cc ? number_format($vehicle->engine_cc) . ' cc' : null,
                                'Steering' => $vehicle->steering ? strtoupper($vehicle->steering) : null,
                                'Ref code' => $vehicle->ref_code,
                                'VIN' => $vehicle->vin,
                            ]);
                        @endphp
                        @foreach ($specs as $label => $value)
                            <div>
                                <dt class="text-overline uppercase text-[rgb(var(--text-muted))] mb-1">{{ $label }}</dt>
                                <dd class="text-body-sm font-medium text-ink {{ in_array($label, ['Ref code', 'VIN']) ? 'font-mono text-caption' : '' }}">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    {{-- Features & specs (D4) --}}
                    @php $featureGroups = $vehicle->groupedFeatures(); @endphp
                    @if (! empty($featureGroups))
                        <div class="mt-6 pt-6 border-t border-line">
                            <h2 class="text-h4 text-ink mb-3">Features &amp; specs</h2>
                            <div class="space-y-4">
                                @foreach ($featureGroups as $group => $values)
                                    <div>
                                        <h3 class="text-overline uppercase text-[rgb(var(--text-muted))] mb-2">{{ $group }}</h3>
                                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1.5 text-body-sm">
                                            @foreach ($values as $fv)
                                                <div class="flex items-center justify-between gap-2 border-b border-line py-1.5">
                                                    <dt class="text-[rgb(var(--text-muted))]">{{ $fv->definition->name }}</dt>
                                                    <dd class="font-medium text-right text-ink">
                                                        @if ($fv->definition->type === 'boolean')
                                                            @if ((int) $fv->value === 1)
                                                                <span class="text-[rgb(var(--success))]">✓ Yes</span>
                                                            @else
                                                                <span class="text-[rgb(var(--text-muted))]">No</span>
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
                        <div class="mt-6 pt-6 border-t border-line">
                            <h2 class="text-h4 text-ink mb-3">Description</h2>
                            <p class="text-body-sm text-[rgb(var(--text))] whitespace-pre-line leading-relaxed">{{ $vehicle->description }}</p>
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- Sticky contact card --}}
            <div class="space-y-5">
                <x-card padding="md" class="lg:sticky lg:top-20">
                    <div class="mb-4">
                        <x-price :value="$vehicle->primaryPrice()" size="xl" />
                        @if ($vehicle->secondaryPrice())
                            <div class="text-body-sm text-[rgb(var(--text-muted))] tabular-nums mt-0.5">{{ $vehicle->secondaryPrice() }}</div>
                        @endif
                    </div>

                    <div class="border-t border-line pt-4 space-y-2 text-body-sm">
                        @unless ($vehicle->ownerIsVerified())
                            <div class="bg-[rgb(var(--warning)/0.12)] border border-[rgb(var(--warning)/0.3)] rounded-lg px-3 py-2">
                                <x-badge variant="unverified" />
                                <span class="text-caption text-[rgb(var(--text-muted))] ml-1">This seller hasn't completed verification yet.</span>
                            </div>
                        @endunless
                        <div class="flex items-center justify-between">
                            <span class="text-[rgb(var(--text-muted))]">Seller type</span>
                            <span class="text-caption font-medium px-2 py-0.5 rounded-full {{ $vehicle->isListedByVendor() ? 'bg-[rgb(var(--info)/0.15)] text-[rgb(var(--info))]' : 'bg-surface-2 text-[rgb(var(--text-muted))]' }}">
                                {{ $vehicle->isListedByVendor() ? 'Dealer' : 'Private' }}
                            </span>
                        </div>
                        @if ($vehicle->isListedByVendor())
                            <div class="flex items-center justify-between">
                                <span class="text-[rgb(var(--text-muted))]">Dealership</span>
                                <span class="text-ink font-medium">{{ $vehicle->vendor?->name }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-[rgb(var(--text-muted))]">Listed</span>
                            <span class="text-[rgb(var(--text))]">{{ $vehicle->created_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    {{-- Contact seller (D6/H4): records a lead, then reveals details --}}
                    <div class="mt-5">
                        <x-contact-bar :contact-url="route('vehicles.contact', $vehicle)" :price="$vehicle->primaryPrice()" />
                    </div>

                    <div class="mt-3 text-center">
                        <x-report-listing :action="route('vehicles.report', $vehicle)" />
                    </div>
                </x-card>
            </div>

        </div>
    </div>

    {{-- H10: parts that fit this vehicle (cross-sell) --}}
    @if ($compatibleParts->isNotEmpty())
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12">
            <h2 class="text-h2 text-ink mb-5">Parts that fit this {{ $vehicle->make?->name }} {{ $vehicle->vehicleModel?->name }}</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach ($compatibleParts as $product)
                    <x-part-card :product="$product" />
                @endforeach
            </div>
        </div>
    @endif

    {{-- H7: other listings this buyer has recently viewed --}}
    {{-- AC2: deterministic similar vehicles --}}
    <x-vehicle-row title="Similar vehicles" :vehicles="$similar" />

    <x-vehicle-row title="Recently viewed" :vehicles="$recentlyViewed" />
</x-layouts.app>
