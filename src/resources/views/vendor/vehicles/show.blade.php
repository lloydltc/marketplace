<x-layouts.app>
    <x-slot:title>{{ $vehicle->displayTitle() }}</x-slot:title>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('vendor.vehicles.index') }}"
               class="text-sm text-neutral-500 hover:text-neutral-700">← Vehicles</a>
            <span class="text-neutral-300">/</span>
            <span class="text-sm text-neutral-700">{{ $vehicle->displayTitle() }}</span>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 space-y-5">
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
                    <div class="flex items-start justify-between mb-5">
                        <h1 class="text-xl font-semibold text-neutral-900">{{ $vehicle->displayTitle() }}</h1>
                        @php
                            $badge = match($vehicle->status) {
                                'active'   => 'bg-green-100 text-green-700',
                                'pending'  => 'bg-amber-100 text-amber-700',
                                'rejected' => 'bg-red-100 text-red-700',
                                default    => 'bg-neutral-100 text-neutral-600',
                            };
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $badge }}">
                            {{ ucfirst($vehicle->status) }}
                        </span>
                    </div>

                    @if ($vehicle->isRejected())
                        <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                            <strong>Rejected.</strong> Please review and edit your listing to resubmit.
                        </div>
                    @elseif ($vehicle->isPending())
                        <div class="mb-5 bg-amber-50 border border-amber-200 text-amber-700 text-sm rounded-lg px-4 py-3">
                            This listing is awaiting admin review.
                        </div>
                    @endif

                    <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
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
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">Condition</dt>
                            <dd class="text-neutral-900 capitalize">{{ $vehicle->condition }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">Body Type</dt>
                            <dd class="text-neutral-900 capitalize">{{ $vehicle->body_type }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">Transmission</dt>
                            <dd class="text-neutral-900 uppercase">{{ $vehicle->transmission }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">Fuel Type</dt>
                            <dd class="text-neutral-900 capitalize">{{ $vehicle->fuel_type }}</dd>
                        </div>
                        @if ($vehicle->engine_cc)
                        <div>
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">Engine</dt>
                            <dd class="text-neutral-900">{{ number_format($vehicle->engine_cc) }} cc</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">Mileage</dt>
                            <dd class="text-neutral-900 tabular-nums">{{ number_format($vehicle->mileage) }} km</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">Color</dt>
                            <dd class="text-neutral-900 capitalize">{{ $vehicle->color }}</dd>
                        </div>
                        @if ($vehicle->vin)
                        <div class="col-span-2">
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">VIN</dt>
                            <dd class="text-neutral-900 font-mono">{{ $vehicle->vin }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">Price ZWL</dt>
                            <dd class="font-semibold text-neutral-900 tabular-nums">ZWL {{ number_format($vehicle->price_zwl, 2) }}</dd>
                        </div>
                        @if ($vehicle->price_usd)
                        <div>
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">Price USD</dt>
                            <dd class="font-semibold text-neutral-900 tabular-nums">USD {{ number_format($vehicle->price_usd, 2) }}</dd>
                        </div>
                        @endif
                    </dl>

                    @if ($vehicle->description)
                        <div class="mt-5 pt-5 border-t border-neutral-100">
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-2">Description</dt>
                            <dd class="text-sm text-neutral-700 whitespace-pre-line">{{ $vehicle->description }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <div class="space-y-4">
                @if ($vehicle->canBeEdited())
                    <a href="{{ route('vendor.vehicles.edit', $vehicle) }}"
                       class="block w-full text-center bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2.5 rounded-lg text-sm transition-colors">
                        Edit Listing
                    </a>
                @endif

                @if (Auth::user()->isVendorAdmin())
                    <div class="bg-white border border-neutral-200 rounded-xl p-4 space-y-2">
                        <div class="text-xs font-medium text-neutral-500 uppercase tracking-wide">Promote</div>
                        @error('promotion')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        @if ($vehicle->isFeatured())
                            <p class="text-xs text-[#B5790F]">★ Featured until {{ $vehicle->featured_until->format('d M Y') }}</p>
                        @endif
                        <div class="flex flex-wrap gap-2">
                            @foreach (['feature' => 'Feature', 'bump' => 'Bump', 'badge' => 'Verified badge'] as $action => $label)
                                <form method="POST" action="{{ route('vendor.vehicles.promote', $vehicle) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="{{ $action }}">
                                    <button type="submit" class="border border-neutral-300 hover:border-[#F0A820] text-neutral-700 text-xs font-medium px-3 py-1.5 rounded-lg transition-colors">{{ $label }}</button>
                                </form>
                            @endforeach
                        </div>
                        <a href="{{ route('vendor.promotions.packages') }}" class="block text-xs text-[#3DB8E8] hover:underline">View dealer packages →</a>
                    </div>
                @endif
                <div class="text-xs text-neutral-500 space-y-1 bg-white border border-neutral-200 rounded-xl p-4">
                    <div>Listed {{ $vehicle->created_at->diffForHumans() }}</div>
                    @if ($vehicle->updated_at->ne($vehicle->created_at))
                        <div>Updated {{ $vehicle->updated_at->diffForHumans() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
