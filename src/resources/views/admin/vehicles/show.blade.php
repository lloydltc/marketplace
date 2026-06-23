<x-layouts.app>
    <x-slot:title>{{ $vehicle->displayTitle() }}</x-slot:title>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('admin.vehicles.index') }}"
               class="text-sm text-neutral-500 hover:text-neutral-700">← Vehicles</a>
            <span class="text-neutral-300">/</span>
            <span class="text-sm text-neutral-700">{{ $vehicle->displayTitle() }}</span>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 space-y-5">
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-neutral-900 mb-5">Vehicle Details</h2>

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
                        @if ($vehicle->hasZwl())
                        <div>
                            <dt class="text-xs text-neutral-500 uppercase tracking-wide mb-1">Price ZWL</dt>
                            <dd class="font-semibold text-neutral-900 tabular-nums">ZWL {{ number_format($vehicle->price_zwl, 2) }}</dd>
                        </div>
                        @endif
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

            <div class="space-y-5">
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-neutral-700 mb-3">Status</h3>
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

                    <div class="mt-4 text-xs text-neutral-500 space-y-1">
                        @if ($vehicle->isListedByVendor())
                            <div>Vendor: <span class="text-neutral-700 font-medium">{{ $vehicle->vendor?->name }}</span></div>
                        @else
                            <div>Seller: <span class="text-neutral-700 font-medium">{{ $vehicle->seller?->name }}</span></div>
                        @endif
                        <div>Listed: {{ $vehicle->created_at->diffForHumans() }}</div>
                    </div>
                </div>

                @if ($vehicle->isPending())
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 space-y-3">
                    <h3 class="text-sm font-semibold text-neutral-700">Review Actions</h3>

                    <form method="POST" action="{{ route('admin.vehicles.approve', $vehicle) }}">
                        @csrf
                        <button type="submit"
                                class="w-full bg-[#2EBD7A] hover:bg-[#2EBD7A]/90 text-white font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                            Approve
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.vehicles.reject', $vehicle) }}" x-data="{ open: false }">
                        @csrf
                        <button type="button" @click="open = !open"
                                class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                            Reject
                        </button>
                        <div x-show="open" class="mt-3">
                            <textarea name="reason" rows="3" required
                                      placeholder="Rejection reason (required)…"
                                      class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-300 resize-none"></textarea>
                            <button type="submit"
                                    class="mt-2 w-full bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                                Confirm Rejection
                            </button>
                        </div>
                    </form>
                </div>
                @endif
            </div>

        </div>
    </div>
</x-layouts.app>
