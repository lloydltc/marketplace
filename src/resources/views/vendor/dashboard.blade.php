<x-layouts.app>
    <x-slot:title>Vendor dashboard</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="lg:flex lg:gap-8">
        <x-vendor-sidebar />
        <div class="flex-1 min-w-0 mt-6 lg:mt-0">

        {{-- Status banner --}}
        @if ($vendor?->isPending())
            <div class="mb-6 bg-[rgb(var(--warning)/0.12)] border border-[rgb(var(--warning)/0.3)] rounded-xl px-5 py-4 flex items-start gap-3">
                <svg class="size-5 text-[rgb(var(--warning))] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <div>
                    <p class="text-body-sm font-semibold text-ink">Account pending approval</p>
                    <p class="text-body-sm text-muted mt-0.5">Your account is being reviewed. Upload your verification documents to speed things up.</p>
                    <div class="mt-2 flex gap-4">
                        <a href="{{ route('vendor.documents.index') }}" class="text-caption font-semibold text-[rgb(var(--info))] hover:underline">Upload documents</a>
                        <a href="{{ route('vendor.bank-accounts.index') }}" class="text-caption font-semibold text-[rgb(var(--info))] hover:underline">Add bank account</a>
                    </div>
                </div>
            </div>
        @elseif ($vendor?->isSuspended())
            <div class="mb-6 bg-[rgb(var(--danger)/0.12)] border border-[rgb(var(--danger)/0.3)] rounded-xl px-5 py-4">
                <p class="text-body-sm font-semibold text-ink">Account suspended</p>
                <p class="text-body-sm text-muted mt-0.5">Your vendor account has been suspended. Contact
                    <a href="mailto:info@salmadrive.co.zw" class="text-[rgb(var(--info))] underline">info@salmadrive.co.zw</a> for assistance.</p>
            </div>
        @endif

        {{-- Header --}}
        <div class="flex items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-h1 text-ink">{{ $vendor?->name ?? 'Vendor dashboard' }}</h1>
                <p class="text-body-sm text-muted mt-1">Manage your listings, team, and orders.</p>
            </div>
            @if (Auth::user()->isVendorAdmin())
                <div class="flex items-center gap-2">
                    <x-button variant="outline" :href="route('vendor.profile.show')">Profile</x-button>
                    <x-button :href="route('vendor.invitation.create')">+ Invite member</x-button>
                </div>
            @endif
        </div>

        <x-renew-prompt :vehicles="$attentionVehicles" renew-route="vendor.vehicles.renew" />

        {{-- KPI tiles --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            @foreach ([
                ['Active listings', number_format($stats['active_listings'])],
                ['Pending orders', number_format($stats['pending_orders'])],
                ['Team members', number_format($stats['team_members'])],
                ['Tier', $stats['tier'] ?? '—'],
            ] as [$label, $value])
                <div class="bg-surface border border-line rounded-lg p-5 shadow-e1">
                    <p class="text-overline uppercase text-muted">{{ $label }}</p>
                    <p class="mt-2 text-display-lg tabular-nums text-ink leading-none capitalize">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        {{-- Listing management --}}
        @if (Auth::user()->isVendorAdmin())
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <a href="{{ route('vendor.products.create') }}"
                   class="group bg-surface border border-line rounded-lg p-6 shadow-e1 hover:border-brand hover:shadow-e2 transition-all flex items-center justify-between">
                    <div>
                        <h3 class="text-h4 text-ink">List a product</h3>
                        <p class="text-body-sm text-muted mt-0.5">Add spare parts, accessories, or tools.</p>
                    </div>
                    <span class="text-h2 text-brand">+</span>
                </a>
                <a href="{{ route('vendor.vehicles.create') }}"
                   class="group bg-surface border border-line rounded-lg p-6 shadow-e1 hover:border-brand hover:shadow-e2 transition-all flex items-center justify-between">
                    <div>
                        <h3 class="text-h4 text-ink">List a vehicle</h3>
                        <p class="text-body-sm text-muted mt-0.5">Add a car, truck, or other vehicle.</p>
                    </div>
                    <span class="text-h2 text-brand">+</span>
                </a>
            </div>
        @endif

        </div>
      </div>
    </div>
</x-layouts.app>
